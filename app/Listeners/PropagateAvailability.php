<?php

namespace App\Listeners;

use App\Events\PhaseCompleted;
use App\Models\OrdineFase;
use App\Models\Ordine;
use App\Modules\Scheduling\Services\PropagazioneService;
use App\Services\PriorityService;
use Carbon\Carbon;

/**
 * Mossa 37 — Strato 1: propagazione istantanea (<100ms)
 *
 * Quando un operatore completa una fase:
 * 1. Trova tutti i successori (stessa commessa, sequenza maggiore)
 * 2. Per ogni successore, controlla se TUTTI i predecessori sono completati/in corso
 * 3. Se sì → disponibile_m37 = true
 * 4. Ricalcola urgenza_reale, fascia_urgenza, priorita_m37
 *
 * Strangler Fig: oltre alla ricalcolazione "ricca" di {@see PriorityService}
 * (campi DB persistiti), invochiamo anche {@see PropagazioneService} del
 * modulo Scheduling per allineare la transizione di stato (PRONTA/disponibile)
 * usando le rule pure (SequenzaFasiRule). I due path coesistono finche la
 * migrazione del PriorityService legacy non e completa.
 */
class PropagateAvailability
{
    public function __construct(
        private readonly ?PropagazioneService $propagazione = null,
    ) {
    }

    public function handle(PhaseCompleted $event): void
    {
        // 1) Persistenza campi Mossa37 (logica legacy invariata).
        PriorityService::propagaDisponibilita($event->fase);

        // 2) Transizione di stato pure (modulo Scheduling).
        if ($this->propagazione !== null) {
            $this->propagazione->propagaFineFase($event->fase);
        }
    }
}
