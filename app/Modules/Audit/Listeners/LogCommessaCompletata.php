<?php

declare(strict_types=1);

namespace App\Modules\Audit\Listeners;

use App\Modules\Audit\Enums\EntitaAudit;
use App\Modules\Audit\Enums\TipoAzione;
use App\Modules\Audit\Services\AuditLogService;
use App\Modules\Commessa\Events\CommessaCompletata;

/**
 * Logga il completamento di tutte le fasi di una commessa.
 *
 * `cod_commessa` è una stringa (es. "0067164-26") quindi entitaId va come
 * string — il VO AuditEvent gestisce il dispatch corretto verso `extra`.
 */
final class LogCommessaCompletata
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function handle(CommessaCompletata $event): void
    {
        $this->audit->log(
            azione:   TipoAzione::Update,
            entita:   EntitaAudit::Commessa,
            entitaId: $event->codCommessa,
            dopo:     ['stato' => 'completata'],
            extra:    'tutte le fasi terminate',
        );
    }
}
