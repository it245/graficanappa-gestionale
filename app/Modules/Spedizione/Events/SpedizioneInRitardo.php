<?php

declare(strict_types=1);

namespace App\Modules\Spedizione\Events;

use App\Models\DdtSpedizione;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento dispatchato quando RitardoRule::dovrebbeNotificare() scatta su un DDT.
 *
 * Listener dedicati possono inviare email, push, log dedicati senza dover
 * conoscere la regola di business.
 */
final class SpedizioneInRitardo
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly DdtSpedizione $ddt,
        public readonly int $giorniRitardo,
    ) {}
}
