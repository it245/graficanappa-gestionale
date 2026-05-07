<?php

declare(strict_types=1);

namespace App\Modules\Onda\ValueObjects;

use InvalidArgumentException;

/**
 * Codice commessa nel formato MES.
 *
 * Onda fornisce numeri "grezzi" estratti da descrizioni DDT (es. "Commessa n° 12345"),
 * il MES li normalizza in "0012345-26" (7 cifre zero-padded + trattino + anno 2 cifre).
 *
 * Centralizza qui il parsing per evitare regex duplicate in OndaSyncService.
 */
final class CodiceCommessaOnda
{
    /**
     * Formato MES: ^[0-9]{7}-[0-9]{2}$
     */
    public const FORMATO = '/^[0-9]{7}-[0-9]{2}$/';

    public function __construct(
        public readonly string $codice,
    ) {
        if (!preg_match(self::FORMATO, $codice)) {
            throw new InvalidArgumentException(
                "CodiceCommessaOnda: formato non valido '{$codice}', atteso NNNNNNN-AA."
            );
        }
    }

    /**
     * Costruisce un codice commessa MES da numero "grezzo" + anno (yy).
     *
     * @param string|int $numeroGrezzo Numero estratto dalla descrizione (es. "12345" o "00012345")
     * @param string|int $annoYY       Anno a 2 cifre (es. "26" per 2026)
     */
    public static function da(string|int $numeroGrezzo, string|int $annoYY): self
    {
        $numero = str_pad((string) $numeroGrezzo, 7, '0', STR_PAD_LEFT);
        $anno = str_pad((string) $annoYY, 2, '0', STR_PAD_LEFT);
        return new self($numero . '-' . $anno);
    }

    /**
     * Estrae un codice commessa da una stringa libera (descrizione DDT Onda).
     *
     * Match: "Commessa n° 12345", "Commessa 12345", "Commessa nº 12345" ecc.
     * Per costruire il codice MES serve anche l'anno (es. dalla DataDocumento DDT).
     *
     * @return string|null Numero "grezzo" (5-7 cifre) o null se non trovato.
     */
    public static function estraiNumeroDaDescrizione(?string $descrizione): ?string
    {
        if ($descrizione === null || $descrizione === '') {
            return null;
        }
        if (preg_match('/Commessa\s*n?[°º.]?\s*(\d{5,7})/iu', $descrizione, $m)) {
            return $m[1];
        }
        return null;
    }

    public function __toString(): string
    {
        return $this->codice;
    }
}
