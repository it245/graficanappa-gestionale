<?php

declare(strict_types=1);

namespace App\Modules\Fasi\Listeners;

use App\Modules\Fasi\Events\FaseTerminata;
use App\Modules\Scheduling\Services\PropagazioneService;

/**
 * Quando una fase termina, propaga la disponibilità alle fasi successive
 * (sblocca i ProntoStato dei nodi a valle nella sequenza_fasi).
 *
 * Strangler Fig: delega a {@see PropagazioneService} (modulo Scheduling)
 * per la logica pura di propagazione, in modo da poterla riusare anche
 * dal listener legacy {@see \App\Listeners\PropagateAvailability}.
 */
final class PropagaFasiSuccessiveListener
{
    public function __construct(
        private readonly PropagazioneService $propagazione,
    ) {
    }

    public function handle(FaseTerminata $event): void
    {
        // Recupera la fase dall'evento di dominio. Lo stub iniziale
        // non aveva payload definito; supportiamo entrambe le shape
        // (proprietà fase oppure ordineFase) finche FaseTerminata non
        // viene canonizzato definitivamente.
        $fase = $event->fase ?? $event->ordineFase ?? null;

        if ($fase === null) {
            return;
        }

        $this->propagazione->propagaFineFase($fase);
    }
}
