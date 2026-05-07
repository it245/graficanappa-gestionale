<?php

declare(strict_types=1);

namespace App\Modules\Fustelle\Events;

use App\Models\Fustella;
use App\Models\Operatore;
use App\Models\Ordine;
use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Emesso quando una fustella viene prelevata dal magazzino per una commessa.
 */
class FustellaPrelevata
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Fustella $fustella,
        public readonly Operatore $operatore,
        public readonly Ordine $ordine,
        public readonly Carbon $quando,
    ) {
    }
}
