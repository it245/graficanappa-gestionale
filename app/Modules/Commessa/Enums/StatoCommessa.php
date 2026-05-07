<?php

declare(strict_types=1);

namespace App\Modules\Commessa\Enums;

/**
 * Stati derivati di una commessa, aggregati a partire dagli stati delle fasi
 * e delle quantità consegnate (qta_ddt_vendita).
 *
 * Gli stati delle singole fasi (ordine_fasi.stato) sono:
 *   0 = caricato, 1 = pronto, 2 = avviato, 3 = terminato, 4 = consegnato
 *
 * Questo enum li proietta a livello di COMMESSA (N ordini, N fasi).
 *
 * @example
 *   $stato = StatoCommessa::InCorso;
 *   echo $stato->label(); // "In corso"
 *   $stato->eFinale();    // false
 */
enum StatoCommessa: string
{
    case Aperta = 'aperta';
    case InCorso = 'in_corso';
    case CompletataInternamente = 'completata_internamente';
    case ConsegnataParziale = 'consegnata_parziale';
    case ConsegnataTotale = 'consegnata_totale';
    case Bloccata = 'bloccata';
    case Annullata = 'annullata';

    public function label(): string
    {
        return match ($this) {
            self::Aperta => 'Aperta',
            self::InCorso => 'In corso',
            self::CompletataInternamente => 'Completata',
            self::ConsegnataParziale => 'Consegnata parziale',
            self::ConsegnataTotale => 'Consegnata totale',
            self::Bloccata => 'Bloccata',
            self::Annullata => 'Annullata',
        };
    }

    /**
     * True se lo stato non evolve più (consegnata totale o annullata).
     */
    public function eFinale(): bool
    {
        return match ($this) {
            self::ConsegnataTotale, self::Annullata => true,
            default => false,
        };
    }

    /**
     * True se la produzione interna è terminata (a prescindere dalla consegna al cliente).
     */
    public function eProduzioneCompletata(): bool
    {
        return match ($this) {
            self::CompletataInternamente,
            self::ConsegnataParziale,
            self::ConsegnataTotale => true,
            default => false,
        };
    }
}
