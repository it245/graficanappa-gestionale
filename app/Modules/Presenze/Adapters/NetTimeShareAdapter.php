<?php

declare(strict_types=1);

namespace App\Modules\Presenze\Adapters;

use App\Modules\Presenze\Contracts\TimbratureSourceInterface;
use App\Modules\Presenze\Enums\TipoTimbratura;
use App\Modules\Presenze\ValueObjects\BadgeOperatore;
use App\Modules\Presenze\ValueObjects\PeriodoPresenza;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Adapter NetTime: legge dalla tabella `nettime_timbrature` popolata
 * dal cron `php artisan presenze:sync` (ogni 5 min) che a sua volta
 * legge da \\192.168.1.34\NetTime\TIMBRA\TIMBRACP.BKP (primario) o
 * \\192.168.1.253\timbrature\timbrature.txt (fallback).
 *
 * Schema esistente preservato (NON modificato — vedi
 * {@see \App\Console\Commands\SyncPresenze}):
 *   - nettime_timbrature(matricola, data_ora, verso E/U, terminale)
 *   - nettime_anagrafica(matricola, cognome, nome)
 *
 * Pairing E↔U:
 *  - turno notturno: E ieri (>= 20:00) senza U prima di mezzanotte
 *    → periodo del giorno successivo
 *  - doppio badge < 2 min: ignora la prima E
 *  - rientro pausa (>= 2h tra due E consecutive): nuovo periodo
 *
 * La logica replica `PresenzeController::index()` legacy in modo
 * deterministico, così il refactor non cambia comportamento utente.
 */
final class NetTimeShareAdapter implements TimbratureSourceInterface
{
    private const SHARE_PRIMARY = '\\\\192.168.1.34\\NetTime\\TIMBRA\\TIMBRACP.BKP';
    private const SHARE_FALLBACK = '\\\\192.168.1.253\\timbrature\\timbrature.txt';

    public function sourceId(): string
    {
        return 'nettime_share';
    }

    public function isAvailable(): bool
    {
        // Lo state "available" è quello del DB locale (popolato dal cron),
        // non del filesystem share — perché il cron è asincrono e i
        // service del modulo leggono da DB. La share è verificata dal
        // cron stesso (SyncPresenze::ensureNetUse).
        try {
            return DB::getSchemaBuilder()->hasTable('nettime_timbrature');
        } catch (\Throwable $e) {
            Log::warning('NetTimeShareAdapter::isAvailable check fallito', ['err' => $e->getMessage()]);
            return false;
        }
    }

    public function ultimaTimbratura(string $badge): ?CarbonInterface
    {
        $matricola = (new BadgeOperatore($badge))->matricola;
        $row = DB::table('nettime_timbrature')
            ->where('matricola', $matricola)
            ->orderByDesc('data_ora')
            ->first(['data_ora']);

        return $row ? Carbon::parse($row->data_ora) : null;
    }

    /**
     * @return Collection<int, PeriodoPresenza>
     */
    public function timbratureDelGiorno(CarbonInterface $giorno): Collection
    {
        $oggi  = $giorno->copy()->startOfDay();
        $ieri  = $oggi->copy()->subDay();
        $domani = $oggi->copy()->addDay();

        // Range esteso: ieri 20:00 → fine giorno (per turno notturno).
        $righe = DB::table('nettime_timbrature')
            ->where('data_ora', '>=', $ieri->copy()->setTime(20, 0)->format('Y-m-d H:i:s'))
            ->where('data_ora', '<', $domani->format('Y-m-d H:i:s'))
            ->orderBy('matricola')
            ->orderBy('data_ora')
            ->get(['matricola', 'data_ora', 'verso']);

        $periodi = collect();
        foreach ($righe->groupBy('matricola') as $matricola => $rs) {
            $rs = $rs->values();

            // Filtra notturne (E ieri sera senza U entro mezzanotte).
            $notturna = null;
            $oggiRighe = collect();
            foreach ($rs as $r) {
                if (str_starts_with((string) $r->data_ora, $ieri->format('Y-m-d'))) {
                    if ($r->verso === 'E') {
                        // Verifica: c'è una U dopo ma prima di mezzanotte?
                        $haUscita = $rs->contains(fn ($x) =>
                            $x->verso === 'U'
                            && $x->data_ora > $r->data_ora
                            && $x->data_ora < $oggi->format('Y-m-d H:i:s')
                        );
                        if (!$haUscita) {
                            $notturna = $r; // entrata notturna che continua su oggi
                        }
                    }
                } else {
                    $oggiRighe->push($r);
                }
            }

            // Pulizia: rimuovi E doppie consecutive < 2h
            $pulite = collect();
            $count = $oggiRighe->count();
            for ($i = 0; $i < $count; $i++) {
                $curr = $oggiRighe[$i];
                $next = $oggiRighe[$i + 1] ?? null;
                if ($next && $curr->verso === 'E' && $next->verso === 'E') {
                    $diffMin = (int) abs(
                        Carbon::parse($curr->data_ora)->diffInMinutes(Carbon::parse($next->data_ora))
                    );
                    if ($diffMin < 120) {
                        continue;
                    }
                }
                $pulite->push($curr);
            }

            // Se c'è una notturna, la prependo come ingresso del primo periodo.
            $sequence = $pulite->all();
            if ($notturna !== null) {
                array_unshift($sequence, $notturna);
            }

            // Pairing E → U
            $badge = (string) (new BadgeOperatore((string) $matricola));
            $entrata = null;
            foreach ($sequence as $r) {
                if ($r->verso === 'E') {
                    if ($entrata !== null) {
                        // E seguita da E senza U intermedia: chiudo periodo precedente
                        // come "aperto" (uscita = null) e ne apro uno nuovo.
                        $periodi->push(new PeriodoPresenza(
                            badge: new BadgeOperatore($badge),
                            ingresso: Carbon::parse($entrata->data_ora),
                            uscita: null,
                        ));
                    }
                    $entrata = $r;
                } elseif ($r->verso === 'U' && $entrata !== null) {
                    $periodi->push(new PeriodoPresenza(
                        badge: new BadgeOperatore($badge),
                        ingresso: Carbon::parse($entrata->data_ora),
                        uscita:   Carbon::parse($r->data_ora),
                    ));
                    $entrata = null;
                }
            }
            // Periodo aperto rimasto (operatore ancora dentro).
            if ($entrata !== null) {
                $periodi->push(new PeriodoPresenza(
                    badge: new BadgeOperatore($badge),
                    ingresso: Carbon::parse($entrata->data_ora),
                    uscita: null,
                ));
            }
        }

        return $periodi->values();
    }

    /** @internal usato dai test/diagnostica per esporre i path share */
    public static function sharePaths(): array
    {
        return [
            'primary' => self::SHARE_PRIMARY,
            'fallback' => self::SHARE_FALLBACK,
        ];
    }
}
