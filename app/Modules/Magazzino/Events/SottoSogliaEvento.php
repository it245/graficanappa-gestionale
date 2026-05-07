<?php

declare(strict_types=1);

namespace App\Modules\Magazzino\Events;

use App\Models\MagazzinoArticolo;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento dispatchato quando la giacenza totale di un articolo
 * scende sotto la `soglia_minima`.
 *
 * Listener tipici:
 *  - Notifica Telegram al buyer
 *  - Email RDA automatica
 *  - Aggiornamento dashboard owner (badge)
 *
 * @example
 *  event(new SottoSogliaEvento($articolo, giacenzaCorrente: 120, sogliaMinima: 500));
 */
final class SottoSogliaEvento
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly MagazzinoArticolo $articolo,
        public readonly int $giacenzaCorrente,
        public readonly int $sogliaMinima,
    ) {
    }

    /**
     * Quanti pezzi mancano per tornare in soglia.
     */
    public function fabbisognoMinimo(): int
    {
        return max(0, $this->sogliaMinima - $this->giacenzaCorrente);
    }
}
