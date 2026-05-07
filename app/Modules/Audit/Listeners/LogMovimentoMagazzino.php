<?php

declare(strict_types=1);

namespace App\Modules\Audit\Listeners;

use App\Modules\Audit\Enums\EntitaAudit;
use App\Modules\Audit\Enums\TipoAzione;
use App\Modules\Audit\Services\AuditLogService;
use App\Modules\Magazzino\Events\SottoSogliaEvento;

/**
 * Logga gli eventi rilevanti del magazzino.
 *
 * Per ora ascolta SottoSogliaEvento (l'unico evento Modules/Magazzino/Events
 * presente). Quando saranno emessi eventi `MovimentoCarico`/`MovimentoScarico`
 * espliciti aggiungere altri handle qui o splittare in listener dedicati.
 */
final class LogMovimentoMagazzino
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function handle(SottoSogliaEvento $event): void
    {
        $this->audit->log(
            azione:   TipoAzione::Update,
            entita:   EntitaAudit::Magazzino,
            entitaId: $event->articolo->id ?? null,
            dopo: [
                'codice'          => $event->articolo->codice ?? null,
                'giacenza'        => $event->giacenzaCorrente,
                'soglia_minima'   => $event->sogliaMinima,
                'fabbisogno'      => $event->fabbisognoMinimo(),
            ],
            extra: 'sotto soglia minima',
        );
    }
}
