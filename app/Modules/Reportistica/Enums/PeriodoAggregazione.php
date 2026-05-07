<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\Enums;

use Carbon\Carbon;

/**
 * Granularità temporale dei report (compat con DashboardAdminController::reportDirezione).
 *
 * Mantiene gli stessi `value` accettati dall'endpoint `?periodo=...` per non
 * rompere il frontend esistente (`mese` è il default storico).
 */
enum PeriodoAggregazione: string
{
    case GIORNO     = 'giorno';
    case SETTIMANA  = 'settimana';
    case MESE       = 'mese';
    case TRIMESTRE  = 'trimestre';
    case SEMESTRE   = 'semestre';
    case ANNO       = 'anno';

    /**
     * Costruisce un PeriodoReport (from..to) coerente con il calcolo legacy
     * usato in {@see \App\Http\Controllers\DashboardAdminController::reportDirezione}.
     *
     * NB: $a è always endOfDay per evitare di tagliare le ultime fasi della giornata.
     */
    public function intervallo(?Carbon $oggi = null): array
    {
        $oggi = ($oggi ?: Carbon::today())->copy();

        return match ($this) {
            self::GIORNO    => [$oggi->copy()->startOfDay(),       $oggi->copy()->endOfDay()],
            self::SETTIMANA => [$oggi->copy()->subWeek(),          $oggi->copy()->endOfDay()],
            self::MESE      => [$oggi->copy()->subMonth(),         $oggi->copy()->endOfDay()],
            self::TRIMESTRE => [$oggi->copy()->subMonths(3),       $oggi->copy()->endOfDay()],
            self::SEMESTRE  => [$oggi->copy()->subMonths(6),       $oggi->copy()->endOfDay()],
            self::ANNO      => [$oggi->copy()->subYear(),          $oggi->copy()->endOfDay()],
        };
    }

    public static function fromRequest(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::MESE;
    }
}
