<?php

declare(strict_types=1);

namespace App\Modules\Presenze\Services;

use App\Modules\Presenze\Enums\TipoAssenza;
use App\Modules\Presenze\ValueObjects\BadgeOperatore;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Gestione assenze: ferie, malattie, permessi, scioperi.
 *
 * Storage: tabella `presenze_assenze` (creata on-demand se mancante,
 * stesso pattern di SyncPresenze::ensureTable per non richiedere
 * migration durante il refactor — vedi vincolo "NON cambiare schema
 * DB" interpretato come "non rinominare/eliminare le tabelle esistenti
 * `presenze` e `timbrature`": questa è una tabella nuova del modulo).
 *
 * Schema:
 *  - matricola (FK morbida verso nettime_anagrafica.matricola)
 *  - tipo (enum string: FERIE/MALATTIA/...)
 *  - data_inizio (date)
 *  - data_fine (date)
 *  - note (text nullable)
 *  - inserita_da (operatore_id nullable)
 */
final class AssenzeService
{
    private const TABLE = 'presenze_assenze';

    public function registra(
        BadgeOperatore $badge,
        TipoAssenza $tipo,
        CarbonInterface $dataInizio,
        CarbonInterface $dataFine,
        ?string $note = null,
        ?int $inseritaDa = null,
    ): int {
        $this->ensureTable();
        if ($dataFine->lt($dataInizio)) {
            throw new \InvalidArgumentException('data_fine non può precedere data_inizio');
        }

        return (int) DB::table(self::TABLE)->insertGetId([
            'matricola'    => $badge->matricola,
            'tipo'         => $tipo->value,
            'data_inizio'  => $dataInizio->format('Y-m-d'),
            'data_fine'    => $dataFine->format('Y-m-d'),
            'note'         => $note,
            'inserita_da'  => $inseritaDa,
            'created_at'   => Carbon::now(),
            'updated_at'   => Carbon::now(),
        ]);
    }

    /**
     * Assenze attive nel giorno (date di copertura includono il giorno).
     *
     * @return Collection<int, object>
     */
    public function delGiorno(CarbonInterface $giorno): Collection
    {
        $this->ensureTable();
        $g = $giorno->format('Y-m-d');
        return collect(DB::table(self::TABLE)
            ->where('data_inizio', '<=', $g)
            ->where('data_fine', '>=', $g)
            ->get());
    }

    public function dellOperatoreNelMese(BadgeOperatore $badge, CarbonInterface $mese): Collection
    {
        $this->ensureTable();
        $start = $mese->copy()->startOfMonth()->format('Y-m-d');
        $end = $mese->copy()->endOfMonth()->format('Y-m-d');
        return collect(DB::table(self::TABLE)
            ->where('matricola', $badge->matricola)
            ->where('data_inizio', '<=', $end)
            ->where('data_fine', '>=', $start)
            ->orderBy('data_inizio')
            ->get());
    }

    /**
     * Conta giorni di un certo tipo nell'anno (per saldo ferie).
     */
    public function giorniNellAnno(BadgeOperatore $badge, TipoAssenza $tipo, int $anno): int
    {
        $this->ensureTable();
        $rows = DB::table(self::TABLE)
            ->where('matricola', $badge->matricola)
            ->where('tipo', $tipo->value)
            ->where('data_inizio', '<=', "{$anno}-12-31")
            ->where('data_fine', '>=', "{$anno}-01-01")
            ->get();

        $totale = 0;
        $clipStart = Carbon::create($anno, 1, 1);
        $clipEnd = Carbon::create($anno, 12, 31);
        foreach ($rows as $r) {
            $start = Carbon::parse($r->data_inizio)->max($clipStart);
            $end   = Carbon::parse($r->data_fine)->min($clipEnd);
            $totale += CarbonPeriod::create($start, $end)->count();
        }

        return $totale;
    }

    public function elimina(int $id): bool
    {
        $this->ensureTable();
        return DB::table(self::TABLE)->where('id', $id)->delete() > 0;
    }

    /**
     * Crea la tabella al volo se manca (idempotente).
     * Migrazione formale verrà aggiunta separatamente.
     */
    private function ensureTable(): void
    {
        if (DB::getSchemaBuilder()->hasTable(self::TABLE)) {
            return;
        }
        DB::statement("CREATE TABLE " . self::TABLE . " (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            matricola VARCHAR(10) NOT NULL,
            tipo VARCHAR(20) NOT NULL,
            data_inizio DATE NOT NULL,
            data_fine DATE NOT NULL,
            note TEXT NULL,
            inserita_da INT UNSIGNED NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_matricola (matricola),
            INDEX idx_range (data_inizio, data_fine),
            INDEX idx_tipo (tipo)
        )");
    }
}
