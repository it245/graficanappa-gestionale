<?php

declare(strict_types=1);

namespace App\Modules\Magazzino\Enums;

/**
 * Stato logico di una giacenza articolo.
 *
 * "EsauritoArrivoBolla": qta = 0 ma esiste un movimento di carico previsto
 * (es. bolla OCR già processata, attesa ingresso fisico).
 *
 * @example
 *  if ($stato === StatoGiacenza::SottoSoglia) {
 *      Notification::send($buyer, new RichiestaRiordino($articolo));
 *  }
 */
enum StatoGiacenza: string
{
    case Disponibile           = 'disponibile';
    case SottoSoglia           = 'sotto_soglia';
    case EsauritoArrivoBolla   = 'esaurito_arrivo_bolla';
    case Bloccato              = 'bloccato';

    public function label(): string
    {
        return match ($this) {
            self::Disponibile         => 'Disponibile',
            self::SottoSoglia         => 'Sotto soglia',
            self::EsauritoArrivoBolla => 'Esaurito (arrivo bolla)',
            self::Bloccato            => 'Bloccato',
        };
    }

    /**
     * Codice colore Tailwind/Bootstrap suggerito per badge UI.
     */
    public function colore(): string
    {
        return match ($this) {
            self::Disponibile         => 'green',
            self::SottoSoglia         => 'amber',
            self::EsauritoArrivoBolla => 'orange',
            self::Bloccato            => 'red',
        };
    }
}
