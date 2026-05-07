<?php

declare(strict_types=1);

namespace App\Modules\Fasi\Enums;

/**
 * Enum tipizzato degli stati fase del modulo Fasi.
 *
 * Riproduce gli interi della tabella `ordine_fasi.stato` (vedi
 * App\Constants\StatoFase). Gli stati "stringa" (EXT, motivi pausa)
 * non sono enumerabili e vengono gestiti come `string` nel registry.
 */
enum StatoFase: int
{
    case NonIniziata = 0;
    case Pronta      = 1;
    case Avviata     = 2;
    case Terminata   = 3;
    case Consegnata  = 4;
    case Esterno     = 5;

    /**
     * Verifica se è ammessa la transizione verso $nuovo secondo
     * la matrice canonica del dominio.
     */
    public function puoTransireA(self $nuovo): bool
    {
        return match ($this) {
            self::NonIniziata => $nuovo === self::Pronta,
            self::Pronta      => $nuovo === self::Avviata,
            self::Avviata     => in_array($nuovo, [self::Terminata, self::Esterno], true),
            self::Terminata   => in_array($nuovo, [self::Consegnata, self::Avviata], true),
            self::Consegnata  => false,
            self::Esterno     => $nuovo === self::Terminata,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::NonIniziata => 'Non iniziata',
            self::Pronta      => 'Pronta',
            self::Avviata     => 'Avviata',
            self::Terminata   => 'Terminata',
            self::Consegnata  => 'Consegnata',
            self::Esterno     => 'Esterno',
        };
    }
}
