<?php

declare(strict_types=1);

namespace App\Modules\Notifiche\Contracts;

use App\Modules\Notifiche\Enums\CanaleNotifica;
use App\Modules\Notifiche\ValueObjects\Notifica;

/**
 * Contratto comune per ogni Sender (Telegram/Email/BrowserPush/Toast/Beep).
 *
 * Convenzioni:
 *  - send(): MAI throw — ritorna false su errore (i sender loggano internamente).
 *  - canale(): identifica univocamente il sender al NotificaService (registry).
 *  - isAvailable(): true se le credenziali/dipendenze sono configurate.
 */
interface NotificaSenderInterface
{
    public function send(Notifica $n): bool;

    public function canale(): CanaleNotifica;

    public function isAvailable(): bool;
}
