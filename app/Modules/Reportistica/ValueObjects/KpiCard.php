<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\ValueObjects;

use App\Modules\Reportistica\Enums\TipoKpi;

/**
 * Card KPI mostrata nelle dashboard.
 *
 * `delta` è la variazione % rispetto al periodo precedente (null se non
 * confrontabile). `trend` è il segno qualitativo (up/down/flat) per la UI.
 */
final class KpiCard
{
    /**
     * @param numeric-string|float|int $valore
     */
    public function __construct(
        public readonly TipoKpi $tipo,
        public readonly string $label,
        public readonly float|int|string $valore,
        public readonly ?float $delta = null,
        public readonly string $trend = 'flat',
        public readonly string $unita = '',
    ) {}

    public function toArray(): array
    {
        return [
            'tipo'   => $this->tipo->value,
            'label'  => $this->label,
            'valore' => $this->valore,
            'delta'  => $this->delta,
            'trend'  => $this->trend,
            'unita'  => $this->unita,
        ];
    }

    public static function trendDa(?float $delta): string
    {
        if ($delta === null) {
            return 'flat';
        }
        if ($delta > 0.5) {
            return 'up';
        }
        if ($delta < -0.5) {
            return 'down';
        }
        return 'flat';
    }
}
