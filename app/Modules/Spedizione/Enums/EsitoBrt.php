<?php

declare(strict_types=1);

namespace App\Modules\Spedizione\Enums;

/**
 * Esiti restituiti dal SOAP BRT (campo ESITO della response).
 *
 * Riferimento: BrtService::cercaSpedizione() / getTrackingBySpedizioneId().
 *  -  0 = OK, dati validi
 *  - -1 = sconosciuto / fallback
 *  - -22 = più risultati trovati (DDT multi-spedizione, non tracciabile singolarmente)
 *
 * Gli altri codici BRT non sono sfruttati a livello applicativo: vengono
 * comunque accettati come Errore per non rompere il dominio.
 */
enum EsitoBrt: int
{
    case Ok = 0;
    case Sconosciuto = -1;
    case MultiSpedizione = -22;
    case Errore = -99;

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'OK',
            self::Sconosciuto => 'Sconosciuto',
            self::MultiSpedizione => 'Multi-spedizione',
            self::Errore => 'Errore',
        };
    }

    /**
     * Costruttore tollerante: ogni codice non mappato finisce in Errore.
     */
    public static function fromValoreOSconosciuto(int|string|null $valore): self
    {
        if ($valore === null) {
            return self::Sconosciuto;
        }

        $int = (int) $valore;

        return self::tryFrom($int) ?? self::Errore;
    }

    public function eMultiSpedizione(): bool
    {
        return $this === self::MultiSpedizione;
    }

    public function eOk(): bool
    {
        return $this === self::Ok;
    }
}
