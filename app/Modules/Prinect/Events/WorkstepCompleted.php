<?php

declare(strict_types=1);

namespace App\Modules\Prinect\Events;

use App\Modules\Prinect\ValueObjects\WorkstepId;
use DateTimeImmutable;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Evento dispatchato quando un workstep Prinect transita a COMPLETED
 * (con o senza actualEndDate).
 *
 * Trasporta sia il timestamp di completamento (può essere null se l'API
 * non lo espone — vedi bug 66811) sia i fogli buoni/scarto rilevati,
 * così che i listener non debbano richiamare l'API.
 *
 * Listener tipici:
 *  - aggiornamento ordine_fase.stato = 3 (Terminato);
 *  - cache inchiostro post-fine tiratura (PrinectInkService);
 *  - propagazione fasi successive (Mossa37 PriorityService).
 */
final class WorkstepCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly WorkstepId $workstepId,
        public readonly ?DateTimeImmutable $completatoAt,
        public readonly int $fogliBuoni,
        public readonly int $fogliScarto,
        public readonly ?string $commessaGestionale = null,
    ) {
    }
}
