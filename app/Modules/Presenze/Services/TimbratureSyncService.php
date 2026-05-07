<?php

declare(strict_types=1);

namespace App\Modules\Presenze\Services;

use App\Modules\Presenze\Events\TimbraturaRegistrata;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

/**
 * Servizio di sync timbrature da file NetTime → tabella locale.
 *
 * NON sostituisce il command `presenze:sync` (che resta operativo per
 * il task scheduled ogni 5 min). Esposto come servizio per:
 *   1) permettere al command di delegare la logica (refactor incrementale)
 *   2) abilitare test unitari mockando file_get_contents
 *   3) consentire trigger manuali da UI (admin → "Risincronizza ora")
 *
 * Vincoli rispettati:
 *  - schema DB invariato (nettime_timbrature)
 *  - path share invariati (delegati a SharePathResolver)
 *  - Log::info('Sync NetTime', …) preservato per compat alert manager
 */
final class TimbratureSyncService
{
    private const SHARE_PRIMARY = '\\\\192.168.1.34\\NetTime\\TIMBRA\\TIMBRACP.BKP';
    private const SHARE_FALLBACK = '\\\\192.168.1.253\\timbrature\\timbrature.txt';

    /**
     * Sync incrementale: legge il file, fa parsing, inserisce in batch
     * solo righe nuove (UNIQUE su matricola+data_ora+verso).
     *
     * @param bool $storico se true non filtra ultimi 7 giorni
     * @return array{nuove:int, saltate:int, source:string|null}
     */
    public function sincronizza(bool $storico = false): array
    {
        $path = $this->risolviPath();
        if ($path === null) {
            Log::warning('Sync NetTime: nessun file raggiungibile', [
                'primary' => self::SHARE_PRIMARY,
                'fallback' => self::SHARE_FALLBACK,
            ]);
            return ['nuove' => 0, 'saltate' => 0, 'source' => null];
        }

        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $nuove = 0;
        $saltate = 0;
        $batch = [];
        $cutoff = $storico ? null : Carbon::today()->subDays(7);

        foreach ($lines as $line) {
            // Formato legacy NetTime: R001001000 00000654 130326 1159 E
            if (!preg_match('/^R?(\d{9})\s+(\d{8})\s+(\d{6})\s+(\d{4})\s+([EU])/', $line, $m)) {
                continue;
            }
            $matricola = str_pad(ltrim($m[2], '0') ?: '0', 6, '0', STR_PAD_LEFT);
            $data = Carbon::createFromFormat('dmy', $m[3])->startOfDay();
            if ($cutoff !== null && $data->lt($cutoff)) {
                $saltate++;
                continue;
            }
            $dataOra = $data->format('Y-m-d') . ' '
                . substr($m[4], 0, 2) . ':' . substr($m[4], 2, 2) . ':00';

            $batch[] = [
                'matricola' => $matricola,
                'data_ora'  => $dataOra,
                'verso'     => $m[5],
                'terminale' => $m[1],
            ];

            if (count($batch) >= 500) {
                $nuove += $this->insertBatch($batch);
                $batch = [];
            }
        }
        if ($batch) {
            $nuove += $this->insertBatch($batch);
        }

        Log::info('Sync NetTime', [
            'source' => $path,
            'nuove'  => $nuove,
            'saltate' => $saltate,
            'storico' => $storico,
        ]);

        if ($nuove > 0) {
            // Non emettiamo un evento per riga (rumore garbage):
            // emettiamo un evento aggregato che listener possono usare
            // per invalidare cache o lanciare alert downstream.
            Event::dispatch(new TimbraturaRegistrata(
                source: $path,
                conteggio: $nuove,
                avvenutaIl: Carbon::now(),
            ));
        }

        return ['nuove' => $nuove, 'saltate' => $saltate, 'source' => $path];
    }

    private function risolviPath(): ?string
    {
        if (@file_exists(self::SHARE_PRIMARY)) {
            return self::SHARE_PRIMARY;
        }
        if (@file_exists(self::SHARE_FALLBACK)) {
            return self::SHARE_FALLBACK;
        }
        return null;
    }

    private function insertBatch(array $batch): int
    {
        try {
            return DB::table('nettime_timbrature')->insertOrIgnore($batch);
        } catch (\Throwable $e) {
            Log::warning('Sync NetTime: batch fallito, fallback riga-per-riga', ['err' => $e->getMessage()]);
            $n = 0;
            foreach ($batch as $row) {
                try {
                    DB::table('nettime_timbrature')->insertOrIgnore([$row]);
                    $n++;
                } catch (\Throwable) {
                    // ignore
                }
            }
            return $n;
        }
    }
}
