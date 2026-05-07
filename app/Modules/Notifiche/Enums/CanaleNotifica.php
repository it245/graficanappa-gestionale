<?php

declare(strict_types=1);

namespace App\Modules\Notifiche\Enums;

/**
 * Canali di trasporto disponibili per inviare una notifica.
 *
 * String backed: il valore stringa coincide col nome chiave usato in config/log/DB.
 * Aggiungere un nuovo canale: aggiungere un case + un Sender che implementa
 * NotificaSenderInterface::canale() restituendo questo case.
 */
enum CanaleNotifica: string
{
    case Telegram = 'telegram';
    case Email = 'email';
    case BrowserPush = 'browser_push';
    case Toast = 'toast';
    case Beep = 'beep';
}
