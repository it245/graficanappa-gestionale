<?php

declare(strict_types=1);

namespace App\Modules\Audit\ValueObjects;

use App\Modules\Audit\Enums\EntitaAudit;
use App\Modules\Audit\Enums\LivelloSicurezza;
use App\Modules\Audit\Enums\TipoAzione;
use Carbon\CarbonImmutable;

/**
 * Evento auditato immutable: action + entità + diff + contesto utente.
 *
 * Costruito da {@see \App\Modules\Audit\Services\AuditLogService} e passato
 * a {@see \App\Modules\Audit\Contracts\AuditSinkInterface::scrivi()} per
 * la persistenza su DB, file o syslog.
 *
 * Note di design:
 *  - timestamp è CarbonImmutable: non riassegnabile dopo costruzione.
 *  - entitaId tipato come `int|string|null` per coprire codici Onda alfanumerici
 *    (es. "0067164-26") oltre agli ID Eloquent integer.
 */
final class AuditEvent
{
    public function __construct(
        public readonly TipoAzione $azione,
        public readonly EntitaAudit $entita,
        public readonly int|string|null $entitaId,
        public readonly DiffPayload $diff,
        public readonly ContestoUtente $contesto,
        public readonly LivelloSicurezza $livello,
        public readonly ?string $extra = null,
        public readonly ?CarbonImmutable $quando = null,
    ) {}

    public function timestamp(): CarbonImmutable
    {
        return $this->quando ?? CarbonImmutable::now();
    }

    /**
     * Rappresentazione array piatta per persistenza (DatabaseSink/FileSink).
     *
     * @return array<string,mixed>
     */
    public function toRow(): array
    {
        return [
            'user_id'    => $this->contesto->userId,
            'user_name'  => $this->contesto->userName,
            'action'     => $this->azione->value,
            'model'      => $this->entita->value,
            'model_id'   => is_int($this->entitaId) ? $this->entitaId : null,
            'old_values' => $this->diff->prima !== null
                ? json_encode($this->diff->prima, JSON_UNESCAPED_UNICODE)
                : null,
            'new_values' => $this->diff->dopo !== null
                ? json_encode($this->diff->dopo, JSON_UNESCAPED_UNICODE)
                : null,
            'ip'         => $this->contesto->ip,
            'user_agent' => $this->contesto->userAgent,
            'extra'      => $this->buildExtra(),
            'created_at' => $this->timestamp()->toDateTimeString(),
        ];
    }

    /**
     * Concatena info aggiuntive che il legacy schema non ha colonne dedicate per:
     * livello, sessione_id, entitaId stringa.
     */
    private function buildExtra(): ?string
    {
        $parts = [];
        if ($this->livello !== LivelloSicurezza::Normale) {
            $parts[] = 'livello=' . $this->livello->value;
        }
        if ($this->contesto->sessioneId !== null) {
            $parts[] = 'sess=' . substr($this->contesto->sessioneId, 0, 12);
        }
        if (is_string($this->entitaId)) {
            $parts[] = 'eid=' . $this->entitaId;
        }
        if ($this->extra !== null && $this->extra !== '') {
            $parts[] = $this->extra;
        }
        return $parts === [] ? null : implode('; ', $parts);
    }
}
