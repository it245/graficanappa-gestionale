<?php

declare(strict_types=1);

namespace App\Modules\Audit\Listeners;

use App\Modules\Audit\Enums\EntitaAudit;
use App\Modules\Audit\Enums\TipoAzione;
use App\Modules\Audit\Services\AuditLogService;
use App\Modules\Fasi\Events\FaseTerminata;

/**
 * Logga la terminazione di una fase (stato 2 → 3).
 */
final class LogFaseTerminata
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function handle(FaseTerminata $event): void
    {
        $this->audit->log(
            azione:   TipoAzione::Update,
            entita:   EntitaAudit::OrdineFase,
            entitaId: $event->fase->id,
            prima:    ['stato' => 2],
            dopo: [
                'stato'        => 3,
                'operatore_id' => $event->operatore?->id,
            ],
            extra: 'fase terminata',
        );
    }
}
