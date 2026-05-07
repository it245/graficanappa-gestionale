<?php

declare(strict_types=1);

namespace App\Modules\Fasi\Events;

use App\Models\OrdineFase;
use App\Models\Operatore;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emesso quando una fase passa a TERMINATA (3).
 */
class FaseTerminata
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly OrdineFase $fase,
        public readonly ?Operatore $operatore = null,
    ) {}
}
