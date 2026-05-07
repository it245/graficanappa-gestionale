<?php

declare(strict_types=1);

namespace App\Modules\Prinect\Events;

use App\Modules\Prinect\ValueObjects\WorkstepId;
use DateTimeImmutable;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Evento dispatchato quando un workstep Prinect transita allo stato RUNNING
 * (prima attività di stampa rilevata sull'XL106).
 *
 * Listener tipici:
 *  - aggiornamento stato fase MES a 2 (Avviato);
 *  - notifica Telegram al capo reparto stampa.
 */
final class WorkstepAvviato
{
    use Dispatchable;

    public function __construct(
        public readonly WorkstepId $workstepId,
        public readonly DateTimeImmutable $avvioAt,
        public readonly ?string $commessaGestionale = null,
    ) {
    }
}
