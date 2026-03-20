<?php

namespace App\Listeners;

use App\Events\PhaseCompleted;
use App\Models\OrdineFase;
use App\Models\Ordine;
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
 */
class PropagateAvailability
{
    public function handle(PhaseCompleted $event): void
    {
        PriorityService::propagaDisponibilita($event->fase);
    }
}
