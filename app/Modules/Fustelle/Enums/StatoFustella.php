<?php

declare(strict_types=1);

namespace App\Modules\Fustelle\Enums;

/**
 * Ciclo di vita di una fustella in archivio.
 *
 * - PREPARAZIONE : in lavorazione presso fustellificio / non ancora pronta
 * - PRONTA       : disponibile in magazzino, può essere prelevata
 * - IN_USO       : prelevata da un operatore per una commessa
 * - ARCHIVIATA   : ritirata dal ciclo (rotta, obsoleta, cliente perso)
 */
enum StatoFustella: string
{
    case PREPARAZIONE = 'PREPARAZIONE';
    case PRONTA = 'PRONTA';
    case IN_USO = 'IN_USO';
    case ARCHIVIATA = 'ARCHIVIATA';

    public function label(): string
    {
        return match ($this) {
            self::PREPARAZIONE => 'In Preparazione',
            self::PRONTA => 'Pronta',
            self::IN_USO => 'In Uso',
            self::ARCHIVIATA => 'Archiviata',
        };
    }

    public function eDisponibile(): bool
    {
        return $this === self::PRONTA;
    }

    /**
     * Stati a cui può transitare lo stato corrente (state machine).
     *
     * @return list<self>
     */
    public function transizioniAmmesse(): array
    {
        return match ($this) {
            self::PREPARAZIONE => [self::PRONTA, self::ARCHIVIATA],
            self::PRONTA => [self::IN_USO, self::ARCHIVIATA],
            self::IN_USO => [self::PRONTA, self::ARCHIVIATA],
            self::ARCHIVIATA => [],
        };
    }

    public function puoTransitareA(self $nuovo): bool
    {
        return in_array($nuovo, $this->transizioniAmmesse(), true);
    }
}
