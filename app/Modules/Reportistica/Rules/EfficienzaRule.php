<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\Rules;

/**
 * Regola pura: calcolo efficienza = ore_previste / ore_effettive * 100.
 *
 * Scelta dell'orientamento: > 100 = sotto budget (buono), < 100 = sforato.
 * È la convenzione storica usata nelle dashboard owner — NON cambiarla
 * senza aggiornare anche il template `owner.report_ore`.
 */
final class EfficienzaRule
{
    public const SOGLIA_OK    = 90.0;   // >=90% → verde
    public const SOGLIA_WARN  = 70.0;   // 70-90 → giallo, <70 → rosso

    public static function calcola(float $orePreviste, float $oreEffettive): ?float
    {
        if ($oreEffettive <= 0.0 || $orePreviste < 0.0) {
            return null;
        }
        return round(($orePreviste / $oreEffettive) * 100.0, 1);
    }

    /**
     * Classifica qualitativa per badge UI.
     */
    public static function badge(?float $efficienza): string
    {
        if ($efficienza === null) {
            return 'na';
        }
        if ($efficienza >= self::SOGLIA_OK) {
            return 'ok';
        }
        if ($efficienza >= self::SOGLIA_WARN) {
            return 'warn';
        }
        return 'critical';
    }
}
