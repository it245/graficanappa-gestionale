<?php

declare(strict_types=1);

namespace App\Modules\Reparti\Enums;

/**
 * Macro-categoria di reparto, usata per raggruppamenti di scheduling,
 * dashboard owner e calcolo capacità aggregata.
 *
 *  - PRESTAMPA   → reparto a monte del ciclo (impianti, RIP, prove colore).
 *  - STAMPA      → produzione foglio stampato (offset / digitale / caldo).
 *  - FINITURA    → trasformazioni post-stampa (plastificazione, fustella,
 *                  legatoria, piegaincolla, finestratura).
 *  - LOGISTICA   → magazzino interno + movimentazioni materie prime.
 *  - SPEDIZIONE  → confezionamento finale, DDT, BRT.
 *  - ESTERNO     → lavorazioni in conto-terzi (4graph, ISMA, LegoKart…).
 */
enum TipoReparto: string
{
    case PRESTAMPA  = 'prestampa';
    case STAMPA     = 'stampa';
    case FINITURA   = 'finitura';
    case LOGISTICA  = 'logistica';
    case SPEDIZIONE = 'spedizione';
    case ESTERNO    = 'esterno';

    public function label(): string
    {
        return match ($this) {
            self::PRESTAMPA  => 'Prestampa',
            self::STAMPA     => 'Stampa',
            self::FINITURA   => 'Finitura',
            self::LOGISTICA  => 'Logistica',
            self::SPEDIZIONE => 'Spedizione',
            self::ESTERNO    => 'Esterno (conto-terzi)',
        };
    }
}
