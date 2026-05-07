<?php

declare(strict_types=1);

namespace App\Modules\Notifiche\Enums;

/**
 * Priorita' di una notifica. Usata da CanaleSceltaRule per decidere
 * su quali canali (uno o piu') instradare il messaggio.
 *
 * Ordine semantico: Bassa < Normale < Alta < Critica.
 */
enum PrioritaNotifica: string
{
    case Bassa = 'bassa';
    case Normale = 'normale';
    case Alta = 'alta';
    case Critica = 'critica';

    /** Peso numerico per confronti rapidi (es. soglie). */
    public function peso(): int
    {
        return match ($this) {
            self::Bassa => 0,
            self::Normale => 1,
            self::Alta => 2,
            self::Critica => 3,
        };
    }
}
