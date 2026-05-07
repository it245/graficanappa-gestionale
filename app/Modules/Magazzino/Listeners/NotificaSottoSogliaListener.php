<?php

declare(strict_types=1);

namespace App\Modules\Magazzino\Listeners;

use App\Modules\Magazzino\Events\SottoSogliaEvento;

/**
 * Invia notifica critica (telegram + email + browser push) quando una
 * carta scende sotto soglia minima di magazzino.
 *
 * TODO:
 *  - risolvere lista canali da config('mes.notifiche.canali_critici')
 *  - per ciascun canale: app(NotificaSenderInterface::class, ['canale' => $c])->invia(...)
 *  - includere link ordine fornitore (se esiste workflow riordino)
 */
final class NotificaSottoSogliaListener
{
    public function handle(SottoSogliaEvento $event): void
    {
        // TODO
    }
}
