<?php

declare(strict_types=1);

namespace App\Modules\Commessa\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * La commessa è stata consegnata al cliente. `parziale = true` quando la
 * spedizione copre solo una parte degli ordini/quantità.
 *
 * @example
 *   CommessaConsegnata::dispatch('0067164-26', parziale: false);
 */
final class CommessaConsegnata
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $codCommessa,
        public readonly bool $parziale = false,
    ) {}
}
