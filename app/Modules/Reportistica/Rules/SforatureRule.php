<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\Rules;

use App\Modules\Reportistica\ValueObjects\RigaReport;
use Illuminate\Support\Collection;

/**
 * Regola pura: identifica le righe "sforate" — ore_eff > ore_prev * (1 + tolleranza).
 *
 * Tolleranza default 20% allineata allo standard storico del Report Ore.
 * Modificare la soglia centralmente qui (e NON in 4 punti diversi del controller)
 * è uno degli obiettivi di questo modulo.
 */
final class SforatureRule
{
    public const TOLLERANZA_DEFAULT = 0.20;

    /**
     * @param  Collection<int, RigaReport> $righe
     * @return Collection<int, RigaReport>
     */
    public static function sforate(Collection $righe, float $tolleranza = self::TOLLERANZA_DEFAULT): Collection
    {
        return $righe->filter(static function (RigaReport $r) use ($tolleranza) {
            if ($r->orePreviste <= 0.0 || $r->oreEffettive <= 0.0) {
                return false;
            }
            return $r->oreEffettive > $r->orePreviste * (1.0 + $tolleranza);
        })->values();
    }

    public static function isSforata(RigaReport $r, float $tolleranza = self::TOLLERANZA_DEFAULT): bool
    {
        if ($r->orePreviste <= 0.0 || $r->oreEffettive <= 0.0) {
            return false;
        }
        return $r->oreEffettive > $r->orePreviste * (1.0 + $tolleranza);
    }
}
