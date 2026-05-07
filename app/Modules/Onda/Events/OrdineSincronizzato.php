<?php

declare(strict_types=1);

namespace App\Modules\Onda\Events;

use App\Models\Ordine;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento dispatchato quando un Ordine MES viene creato/aggiornato dalla sync Onda.
 *
 * Listener possibili (futuri):
 *  - notifica owner se nuova commessa con scadenza < 7gg
 *  - log audit (commessa: cliente, qta, valore)
 *  - cache invalidation dashboard
 */
final class OrdineSincronizzato
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Ordine $ordine,
        public readonly bool $appenaCreato,
    ) {}
}
