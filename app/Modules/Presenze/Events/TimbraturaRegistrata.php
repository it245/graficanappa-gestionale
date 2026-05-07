<?php

declare(strict_types=1);

namespace App\Modules\Presenze\Events;

use Carbon\CarbonInterface;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Evento emesso al termine di un sync NetTime con almeno una nuova
 * timbratura inserita. Listener possibili:
 *  - invalidazione cache `owner_presenti_v2`
 *  - aggiornamento WebSocket dashboard owner
 *  - controllo soglia straordinari (StraordinariSuperati)
 */
final class TimbraturaRegistrata
{
    use Dispatchable;

    public function __construct(
        public readonly string $source,
        public readonly int $conteggio,
        public readonly CarbonInterface $avvenutaIl,
    ) {}
}
