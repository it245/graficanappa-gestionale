<?php

declare(strict_types=1);

namespace App\Modules\Spedizione\ValueObjects;

use InvalidArgumentException;

/**
 * Codice di tracking di una spedizione.
 *
 * Nel MES il "codice" coincide con il numero DDT vendita (Onda) per BRT,
 * perché lo SPEDIZIONE_ID BRT viene risolto runtime via SOAP da BrtService.
 */
final class CodiceTracking
{
    public const CORRIERE_BRT = 'BRT';
    public const CORRIERE_ALTRO = 'altro';

    public function __construct(
        public readonly string $numeroDDT,
        public readonly string $corriere = self::CORRIERE_BRT,
    ) {
        if (trim($numeroDDT) === '') {
            throw new InvalidArgumentException('CodiceTracking: numero DDT vuoto.');
        }

        if (!in_array($corriere, [self::CORRIERE_BRT, self::CORRIERE_ALTRO], true)) {
            throw new InvalidArgumentException("CodiceTracking: corriere non valido '{$corriere}'.");
        }
    }

    /**
     * Numero DDT senza zeri di padding iniziale.
     * BRT richiede il valore numerico "pulito" come riferimento mittente.
     */
    public function numeroDDTNormalizzato(): string
    {
        return ltrim($this->numeroDDT, '0') ?: '0';
    }

    /**
     * URL pubblico di tracking.
     * Per corrieri non BRT torna stringa vuota: nessun link affidabile.
     */
    public function urlTracking(): string
    {
        if ($this->corriere !== self::CORRIERE_BRT) {
            return '';
        }

        // Pagina pubblica BRT (ricerca per riferimento mittente)
        return 'https://vas.brt.it/vas/sped_ricerca.htm?Rif_Mittente=' . urlencode($this->numeroDDTNormalizzato());
    }
}
