<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Rules;

use App\Models\OrdineFase;
use Carbon\CarbonImmutable;

/**
 * Regola pura sulla URGENZA temporale di una fase.
 *
 * Si basa sulla data di consegna risalita dall'ordine. Restituisce
 * giorni residui (negativi se la commessa è in ritardo).
 */
final class UrgenzaRule
{
    /**
     * Giorni a consegna (positivi = futuro, 0 = oggi, negativi = ritardo).
     * Se l'ordine non ha consegna definita ritorna PHP_FLOAT_MAX
     * (nessuna pressione → fondo coda urgenza).
     */
    public static function urgenza(OrdineFase $fase, ?CarbonImmutable $now = null): float
    {
        $now      = $now ?? CarbonImmutable::now();
        $consegna = self::dataConsegna($fase);

        if ($consegna === null) {
            return PHP_FLOAT_MAX;
        }

        return $now->startOfDay()->diffInDays($consegna->startOfDay(), false);
    }

    /**
     * True se la fase rientra entro la soglia di urgenza (default ±5 gg
     * coerente con la finestra di batching Mossa 37).
     */
    public static function eUrgente(OrdineFase $fase, int $sogliaGg = 5): bool
    {
        $u = self::urgenza($fase);

        if ($u === PHP_FLOAT_MAX) {
            return false;
        }

        return $u <= $sogliaGg;
    }

    private static function dataConsegna(OrdineFase $fase): ?CarbonImmutable
    {
        $ordine = $fase->ordine ?? null;
        if ($ordine === null) {
            return null;
        }

        // Lo schema reale `ordini` usa `data_prevista_consegna`. Manteniamo
        // i fallback `data_consegna`/`consegna` per stub di test e per la
        // futura tabella `Spedizione::data_consegna`.
        $raw = $ordine->data_prevista_consegna
            ?? $ordine->data_consegna
            ?? $ordine->consegna
            ?? null;
        if ($raw === null) {
            return null;
        }

        return CarbonImmutable::parse($raw);
    }
}
