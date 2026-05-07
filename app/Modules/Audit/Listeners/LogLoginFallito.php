<?php

declare(strict_types=1);

namespace App\Modules\Audit\Listeners;

use App\Modules\Audit\Services\AuditLogService;
use Illuminate\Auth\Events\Failed;

/**
 * Logga ogni tentativo di login fallito.
 *
 * Livello CRITICO (vedi AuditLogService::logLoginFallito): utile per
 * detection brute-force e audit incidenti sicurezza.
 */
final class LogLoginFallito
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function handle(Failed $event): void
    {
        $username = null;
        if (is_array($event->credentials ?? null)) {
            $username = $event->credentials['email']
                ?? $event->credentials['username']
                ?? $event->credentials['codice']
                ?? null;
        }

        $this->audit->logLoginFallito(
            usernameTentato: is_string($username) ? $username : null,
            motivo:          'credenziali non valide',
        );
    }
}
