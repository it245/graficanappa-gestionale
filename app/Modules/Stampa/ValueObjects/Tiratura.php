<?php

declare(strict_types=1);

namespace App\Modules\Stampa\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object immutabile che descrive la tiratura di una fase di stampa.
 *
 * Tutte le grandezze sono in fogli (non copie multiple per foglio):
 *  - copieRichieste: target di tiratura buona da consegnare
 *  - copieFatte:     fogli buoni già prodotti
 *  - fogliBuoni:     totale fogli buoni rilevati (Prinect/Fiery)
 *  - fogliScarto:    totale fogli scarto rilevati
 *
 * Tipicamente copieFatte === fogliBuoni, ma si tengono separati perché
 * il MES può tracciare copieFatte manualmente prima dell'arrivo dei dati API.
 */
final class Tiratura
{
    public function __construct(
        public readonly int $copieRichieste,
        public readonly int $copieFatte,
        public readonly int $fogliBuoni,
        public readonly int $fogliScarto,
    ) {
        if ($copieRichieste < 0 || $copieFatte < 0 || $fogliBuoni < 0 || $fogliScarto < 0) {
            throw new InvalidArgumentException('Tiratura: nessun valore può essere negativo.');
        }
    }

    /**
     * Percentuale di scarto sul totale fogli passati in macchina (buoni + scarto).
     * Restituisce 0.0 se nessun foglio è stato stampato.
     */
    public function scartoPercentuale(): float
    {
        $totale = $this->fogliBuoni + $this->fogliScarto;
        if ($totale === 0) {
            return 0.0;
        }

        return round(($this->fogliScarto / $totale) * 100.0, 2);
    }

    /**
     * Tiratura completata quando i fogli buoni prodotti raggiungono il target.
     */
    public function completata(): bool
    {
        return $this->copieFatte >= $this->copieRichieste;
    }

    /**
     * Copie ancora da produrre (mai negativo).
     */
    public function copieResidue(): int
    {
        return max(0, $this->copieRichieste - $this->copieFatte);
    }
}
