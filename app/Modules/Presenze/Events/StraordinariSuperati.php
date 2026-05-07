<?php

declare(strict_types=1);

namespace App\Modules\Presenze\Events;

use App\Modules\Presenze\ValueObjects\BadgeOperatore;
use App\Modules\Presenze\ValueObjects\OreLavorate;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Operatore ha superato la soglia 40h settimanali → straordinario.
 *
 * Soglia configurata in `App\Modules\Presenze\Rules\CalcoloStraordinariRule`.
 * Listener tipico: notifica al responsabile + conteggio compensativo.
 */
final class StraordinariSuperati
{
    use Dispatchable;

    public function __construct(
        public readonly BadgeOperatore $badge,
        public readonly OreLavorate $oreSettimana,
        public readonly OreLavorate $straordinari,
        public readonly CarbonInterface $settimanaInizio,
    ) {}
}
