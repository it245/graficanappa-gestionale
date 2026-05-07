<?php

declare(strict_types=1);

namespace App\Modules\Fasi\Listeners;

use App\Modules\Fasi\Events\FaseAvviata;

/**
 * Logga su audit-log l'avvio fase (operatore, macchina, timestamp)
 * e aggiorna eventuali contatori real-time della dashboard owner.
 *
 * TODO:
 *  - audit log append (riusare OrdineFaseObserver o tabella audit_logs)
 *  - broadcast su WebSocket Reverb per dashboard live
 */
final class TracciaInizioFaseListener
{
    public function handle(FaseAvviata $event): void
    {
        // TODO
    }
}
