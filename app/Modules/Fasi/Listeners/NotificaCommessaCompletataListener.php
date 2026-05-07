<?php

declare(strict_types=1);

namespace App\Modules\Fasi\Listeners;

use App\Modules\Fasi\Events\FaseTerminata;

/**
 * Se la fase terminata è l'ultima della commessa, invia notifica
 * "commessa completata" al canale default (telegram) via NotificaSenderInterface.
 *
 * TODO:
 *  - check se tutte le fasi della commessa sono in stato 3/4
 *  - costruire ValueObject NotificaPayload
 *  - dispatch verso channel ['telegram'] (notifiche.canali_default)
 */
final class NotificaCommessaCompletataListener
{
    public function handle(FaseTerminata $event): void
    {
        // TODO
    }
}
