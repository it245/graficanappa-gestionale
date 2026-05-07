<?php

declare(strict_types=1);

namespace App\Modules\Audit\Listeners;

use App\Modules\Audit\Services\AuditLogService;
use Illuminate\Auth\Events\Login;

/**
 * Logga ogni login riuscito (Laravel default Auth event).
 *
 * Per i login operatore via codice (NON usano Laravel Auth ma session+codice)
 * chiamare direttamente AuditLogService::logLogin() dal controller.
 */
final class LogLoginRiuscito
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function handle(Login $event): void
    {
        $user = $event->user;
        if ($user === null) {
            return;
        }

        $this->audit->logLogin(
            userId:   (int) ($user->getAuthIdentifier() ?? 0),
            userName: (string) ($user->name ?? $user->username ?? $user->email ?? 'unknown'),
        );
    }
}
