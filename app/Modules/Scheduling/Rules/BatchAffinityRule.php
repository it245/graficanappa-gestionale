<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Rules;

use App\Models\OrdineFase;

/**
 * Regola di affinità di lotto (batching).
 *
 * Due fasi sono batchabili sulla stessa macchina se condividono:
 *   1) carta (formato/grammatura/tipo)
 *   2) reparto fisico
 *   3) finestra di urgenza ±$finestraGg (default 5)
 *
 * La chiave di batch è deterministica e usata per
 * raggruppare consecutivamente i job ed evitare cambi setup.
 */
final class BatchAffinityRule
{
    public const FINESTRA_GIORNI = 5;

    public static function eBatchabile(
        OrdineFase $a,
        OrdineFase $b,
        int $finestraGg = self::FINESTRA_GIORNI,
    ): bool {
        if ($a->id === $b->id) {
            return false;
        }

        if (! self::stessaCarta($a, $b)) {
            return false;
        }

        if (self::reparto($a) !== self::reparto($b)) {
            return false;
        }

        $ua = UrgenzaRule::urgenza($a);
        $ub = UrgenzaRule::urgenza($b);

        if ($ua === PHP_FLOAT_MAX || $ub === PHP_FLOAT_MAX) {
            return false;
        }

        return abs($ua - $ub) <= $finestraGg;
    }

    /**
     * Chiave compatta scrivibile in `sched_batch_group`.
     */
    public static function calcolaBatchGroup(OrdineFase $fase): string
    {
        $reparto = self::reparto($fase) ?? 'NA';
        $carta   = self::cartaSignature($fase);

        // Bucket di urgenza in finestre da FINESTRA_GIORNI giorni.
        $u = UrgenzaRule::urgenza($fase);
        $bucket = ($u === PHP_FLOAT_MAX)
            ? 'X'
            : (string) (int) floor($u / self::FINESTRA_GIORNI);

        return strtoupper("{$reparto}|{$carta}|U{$bucket}");
    }

    private static function stessaCarta(OrdineFase $a, OrdineFase $b): bool
    {
        return self::cartaSignature($a) === self::cartaSignature($b);
    }

    private static function cartaSignature(OrdineFase $fase): string
    {
        $ordine = $fase->ordine ?? null;
        if ($ordine === null) {
            return '';
        }

        $carta = (string) ($ordine->carta ?? $ordine->descrizione_carta ?? '');
        return preg_replace('/\s+/', '', strtolower($carta)) ?? '';
    }

    private static function reparto(OrdineFase $fase): ?string
    {
        $r = $fase->reparto;
        return is_string($r) && $r !== '' ? strtolower($r) : null;
    }
}
