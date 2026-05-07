<?php

declare(strict_types=1);

namespace App\Modules\Commessa\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tutte le fasi della commessa sono terminate internamente (stato >= 3).
 * Non implica ancora la consegna al cliente: per quella vedi CommessaConsegnata.
 *
 * @example
 *   if ($rule->eCompletata($fasi)) {
 *       CommessaCompletata::dispatch('0067164-26');
 *   }
 */
final class CommessaCompletata
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $codCommessa,
    ) {}
}
