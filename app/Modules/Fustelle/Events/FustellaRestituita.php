<?php

declare(strict_types=1);

namespace App\Modules\Fustelle\Events;

use App\Models\Fustella;
use App\Models\Operatore;
use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Emesso quando una fustella prelevata viene restituita al magazzino.
 */
class FustellaRestituita
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Fustella $fustella,
        public readonly Operatore $operatore,
        public readonly Carbon $quando,
        public readonly ?string $posizioneMagazzino = null,
    ) {
    }
}
