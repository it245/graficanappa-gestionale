<?php

declare(strict_types=1);

namespace App\Modules\Fasi\Listeners;

use App\Modules\Fasi\Events\FaseTerminata;

/**
 * Quando una fase termina, propaga la disponibilità alle fasi successive
 * (sblocca i ProntoStato dei nodi a valle nella sequenza_fasi).
 *
 * TODO: portare qui la logica di App\Listeners\PropagateAvailability
 *       per allinearla al nuovo evento di dominio FaseTerminata.
 */
final class PropagaFasiSuccessiveListener
{
    public function handle(FaseTerminata $event): void
    {
        // TODO: chiamare PriorityService / SchedulerEngineInterface per
        //       ricalcolare priorità sulle fasi a valle dello stesso ordine.
    }
}
