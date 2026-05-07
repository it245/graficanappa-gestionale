<?php

declare(strict_types=1);

namespace App\Modules\Onda\Enums;

/**
 * Tipi documento Onda (campo TipoDocumento di ATTDocTeste / PRDDocTeste).
 *
 * Codici cablati nel gestionale Onda:
 *  - 2 = ordine di produzione (PRD)
 *  - 3 = DDT vendita (cliente)
 *  - 7 = DDT fornitore (lavorazioni esterne)
 *
 * Centralizzati qui per evitare magic numbers sparsi nelle query.
 */
enum TipoDocumentoOnda: int
{
    case OrdineProduzione = 2;
    case DdtVendita = 3;
    case DdtFornitore = 7;

    public function label(): string
    {
        return match ($this) {
            self::OrdineProduzione => 'Ordine di produzione',
            self::DdtVendita       => 'DDT vendita',
            self::DdtFornitore     => 'DDT fornitore',
        };
    }
}
