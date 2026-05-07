<?php

declare(strict_types=1);

namespace App\Modules\Presenze\Enums;

/**
 * Stato istantaneo di un operatore in un dato momento.
 *
 * Derivato dall'ultima timbratura disponibile + assenze del giorno:
 *  - Presente   → ultima timbratura = IN/Rientro
 *  - InPausa    → ultima timbratura = Pausa
 *  - Assente    → nessuna timbratura oggi + giorno lavorativo
 *  - Giustificato → assenza registrata (ferie/malattia/...)
 *  - Uscito     → ultima timbratura = OUT (fine turno)
 */
enum StatoPresenza: string
{
    case Presente     = 'PRESENTE';
    case InPausa      = 'IN_PAUSA';
    case Assente      = 'ASSENTE';
    case Giustificato = 'GIUSTIFICATO';
    case Uscito       = 'USCITO';

    public function label(): string
    {
        return match ($this) {
            self::Presente     => 'Presente',
            self::InPausa      => 'In pausa',
            self::Assente      => 'Assente',
            self::Giustificato => 'Giustificato',
            self::Uscito       => 'Uscito',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Presente     => 'green',
            self::InPausa      => 'yellow',
            self::Assente      => 'red',
            self::Giustificato => 'blue',
            self::Uscito       => 'gray',
        };
    }
}
