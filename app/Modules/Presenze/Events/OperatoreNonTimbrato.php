<?php

declare(strict_types=1);

namespace App\Modules\Presenze\Events;

use App\Modules\Presenze\ValueObjects\BadgeOperatore;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Operatore presente nel MES (login attivo / fase in corso) ma SENZA
 * timbratura di ingresso da oltre 30 minuti.
 *
 * Use case: avviso al responsabile di reparto / RSU.
 * NB: non bloccare il lavoro — è solo un alert, l'operatore potrebbe
 * aver dimenticato il badge o averlo timbrato prima del ripristino del
 * sync (es. NetTime offline 10 minuti).
 */
final class OperatoreNonTimbrato
{
    use Dispatchable;

    public function __construct(
        public readonly BadgeOperatore $badge,
        public readonly int $minutiSenzaTimbratura,
        public readonly CarbonInterface $rilevatoIl,
    ) {}
}
