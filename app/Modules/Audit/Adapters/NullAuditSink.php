<?php

declare(strict_types=1);

namespace App\Modules\Audit\Adapters;

use App\Modules\Audit\Contracts\AuditSinkInterface;
use App\Modules\Audit\ValueObjects\AuditEvent;

/**
 * No-op sink: usato in test unit e in dev locale dove non si vuole
 * sporcare la tabella `audit_logs`.
 *
 * Bind via:
 *   config('mes.audit.sink') = NullAuditSink::class
 */
final class NullAuditSink implements AuditSinkInterface
{
    public function scrivi(AuditEvent $evento): void
    {
        // intentionally empty
    }
}
