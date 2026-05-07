<?php

declare(strict_types=1);

namespace App\Modules\Audit\Contracts;

use App\Modules\Audit\ValueObjects\AuditEvent;

/**
 * Astrazione del backend di persistenza dei log di audit.
 *
 * Permette di sostituire DB → file JSONL → syslog senza toccare i
 * Service che producono eventi. In test si usa NullAuditSink.
 *
 * Implementazioni:
 *  - DatabaseAuditSink: tabella `audit_logs` (default produzione)
 *  - FileAuditSink:     append JSON-Lines su disco (debug, fallback)
 *  - NullAuditSink:     no-op (test, dev locale)
 */
interface AuditSinkInterface
{
    /**
     * Scrive l'evento nel backend. Implementazioni DEVONO essere safe:
     * un fallimento di logging non deve mai propagare al chiamante
     * (l'audit non deve bloccare il business).
     */
    public function scrivi(AuditEvent $evento): void;
}
