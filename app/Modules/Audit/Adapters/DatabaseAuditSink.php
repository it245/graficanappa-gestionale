<?php

declare(strict_types=1);

namespace App\Modules\Audit\Adapters;

use App\Modules\Audit\Contracts\AuditSinkInterface;
use App\Modules\Audit\ValueObjects\AuditEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sink di default: persiste su tabella `audit_logs`.
 *
 * Schema atteso (vedi migration 2026_03_27_100000_create_audit_logs_table):
 *   user_id, user_name, action, model, model_id, old_values, new_values,
 *   ip, user_agent, extra, created_at
 *
 * Comportamento difensivo: in caso di errore SQL si logga warning ma non
 * si lancia eccezione — l'audit log non deve mai bloccare il flusso business.
 */
final class DatabaseAuditSink implements AuditSinkInterface
{
    public function __construct(
        private readonly string $tabella = 'audit_logs',
    ) {}

    public function scrivi(AuditEvent $evento): void
    {
        try {
            DB::table($this->tabella)->insert($evento->toRow());
        } catch (Throwable $e) {
            Log::warning('DatabaseAuditSink::scrivi failed', [
                'errore'  => $e->getMessage(),
                'azione'  => $evento->azione->value,
                'entita'  => $evento->entita->value,
                'eid'     => $evento->entitaId,
            ]);
        }
    }
}
