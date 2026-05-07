<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\Enums;

/**
 * Tipologie grafico Chart.js usate nelle dashboard.
 *
 * I `value` sono i `type` accettati direttamente da Chart.js — così un
 * {@see \App\Modules\Reportistica\ValueObjects\DatasetGrafico} può essere
 * serializzato senza ulteriori mappature.
 */
enum TipoGrafico: string
{
    case LINE     = 'line';
    case BAR      = 'bar';
    case PIE      = 'pie';
    case DOUGHNUT = 'doughnut';
}
