<?php

declare(strict_types=1);

namespace App\Modules\Audit\Listeners;

use App\Modules\Audit\Enums\EntitaAudit;
use App\Modules\Audit\Enums\TipoAzione;
use App\Modules\Audit\Services\AuditLogService;
use App\Modules\Fasi\Events\FaseAvviata;

/**
 * Logga l'avvio di una fase produttiva.
 *
 * Volutamente sincrono: il payload è piccolo (id fase + operatore + flag
 * ripresa) e non pesa sulla request. Se in futuro l'audit diventa hot-path
 * implementare ShouldQueue.
 */
final class LogFaseAvviata
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function handle(FaseAvviata $event): void
    {
        $this->audit->log(
            azione:   TipoAzione::Update,
            entita:   EntitaAudit::OrdineFase,
            entitaId: $event->fase->id,
            prima:    null,
            dopo:     [
                'stato'         => 2,
                'operatore_id'  => $event->operatore?->id,
                'ripresa'       => $event->ripresa,
            ],
            extra: $event->ripresa ? 'fase ripresa da pausa' : 'fase avviata',
        );
    }
}
