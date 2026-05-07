<?php

declare(strict_types=1);

namespace App\Modules\Reparti\Events;

use App\Modules\Reparti\Enums\CodiceReparto;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Evento dominio: la quantità di ore richieste a un reparto in un dato
 * giorno supera la capacità disponibile (turni × macchine).
 *
 * Tipico consumer:
 *  - Listener di notifica owner (Telegram/Email "scheduling overflow")
 *  - Mossa 37 PriorityService → ricalcolo propagazione
 *  - Dashboard owner badge "reparto saturo"
 *
 * Stub: NESSUN listener registrato per ora in ModulesServiceProvider.
 * Quando si vorrà attivare, aggiungere mapping Event => [Listener].
 */
final class RepartoCapacitaSuperata
{
    use Dispatchable;

    public function __construct(
        public readonly CodiceReparto    $reparto,
        public readonly CarbonImmutable  $giorno,
        public readonly int              $oreRichieste,
        public readonly int              $oreDisponibili,
    ) {}

    public function overflow(): int
    {
        return max(0, $this->oreRichieste - $this->oreDisponibili);
    }
}
