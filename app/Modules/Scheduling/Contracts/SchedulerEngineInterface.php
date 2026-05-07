<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Contracts;

use App\Modules\Scheduling\ValueObjects\FineSlot;

/**
 * Contratto per un motore di scheduling Mossa 37.
 *
 * L'implementazione di default è {@see \App\Services\SchedulerService},
 * adattata via Adapter senza modificarla. Permette di sostituire il
 * solver (es. in test o per A/B con un risolutore esterno) preservando
 * il modulo di dominio.
 */
interface SchedulerEngineInterface
{
    /**
     * Esegue la schedulazione e ritorna il piano produttivo.
     *
     * @return array{
     *     fasi_processate: int,
     *     slots: array<int, FineSlot>,
     *     warning: array<int, string>,
     * }
     */
    public function schedula(): array;
}
