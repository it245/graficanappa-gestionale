<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\ValueObjects;

use App\Modules\Reportistica\Enums\PeriodoAggregazione;
use Carbon\Carbon;

/**
 * Intervallo temporale immutabile per i report.
 *
 * Tiene insieme `from`, `to`, `label` e l'enum di granularità. Sostituisce
 * la quaterna `($da, $a, $periodo, $labelPeriodo)` sparpagliata in
 * {@see \App\Http\Controllers\DashboardAdminController::reportDirezione}.
 */
final class PeriodoReport
{
    public function __construct(
        public readonly Carbon $from,
        public readonly Carbon $to,
        public readonly PeriodoAggregazione $aggregazione,
        public readonly string $label,
    ) {}

    public static function da(PeriodoAggregazione $agg, ?Carbon $oggi = null): self
    {
        [$from, $to] = $agg->intervallo($oggi);
        $label = $from->format('d M Y') . ' - ' . $to->format('d M Y');

        return new self($from, $to, $agg, $label);
    }

    /**
     * Periodo precedente di pari ampiezza (per delta YoY/WoW/MoM).
     */
    public function precedente(): self
    {
        $secs = $this->from->diffInSeconds($this->to);
        $prevTo   = $this->from->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subSeconds($secs);
        $label    = $prevFrom->format('d M Y') . ' - ' . $prevTo->format('d M Y');

        return new self($prevFrom, $prevTo, $this->aggregazione, $label);
    }

    public function giorni(): int
    {
        return max((int) $this->from->diffInDays($this->to), 1);
    }
}
