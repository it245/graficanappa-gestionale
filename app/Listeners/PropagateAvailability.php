<?php

namespace App\Listeners;

use App\Events\PhaseCompleted;
use App\Services\PriorityService;

class PropagateAvailability
{
    public function handle(PhaseCompleted $event): void
    {
        PriorityService::propagaDisponibilita($event->fase);
    }
}
