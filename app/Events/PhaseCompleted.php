<?php

namespace App\Events;

use App\Models\OrdineFase;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Mossa 37 — Evento emesso quando un operatore completa una fase.
 * Il listener PropagateAvailability ricalcola disponibilità dei successori.
 */
class PhaseCompleted
{
    use Dispatchable, SerializesModels;

    public OrdineFase $fase;

    public function __construct(OrdineFase $fase)
    {
        $this->fase = $fase;
    }
}
