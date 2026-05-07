<?php

declare(strict_types=1);

namespace App\Modules\Fustelle\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object per codice fustella interno (formato modulo: F-NNNNN-X).
 *
 * Esempi validi:
 *   F-12345-A
 *   F-00001-Z
 *
 * NOTA: i codici "legacy" Onda/MES sono FS#### / KS#### (vedi
 * App\Helpers\FustellaResolver e App\Helpers\DescrizioneParser::parseFustella).
 * Questo VO rappresenta il NUOVO formato canonico per la tabella `fustelle`.
 *
 * Per riconoscere/convertire dai codici legacy usare {@see self::daLegacy()}.
 */
final readonly class CodiceFustella
{
    public const REGEX = '/^F-\d{5}-[A-Z]$/';
    public const REGEX_LEGACY = '/^(FS|KS)\d{3,5}$/';

    public function __construct(
        public string $valore,
    ) {
    }

    /**
     * Costruisce il VO da una stringa, validando il formato canonico.
     *
     * @throws InvalidArgumentException se il formato non è valido.
     */
    public static function daStringa(string $codice): self
    {
        $codice = strtoupper(trim($codice));

        if ($codice === '') {
            throw new InvalidArgumentException('Codice fustella vuoto');
        }

        if (preg_match(self::REGEX, $codice) !== 1) {
            throw new InvalidArgumentException(
                "Codice fustella non valido: '{$codice}'. Formato atteso: F-NNNNN-X"
            );
        }

        return new self($codice);
    }

    /**
     * Tenta il parse senza eccezioni.
     */
    public static function provaDaStringa(string $codice): ?self
    {
        try {
            return self::daStringa($codice);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Riconosce un codice legacy (FS####/KS####).
     * Ritorna null se non è un codice legacy.
     */
    public static function daLegacy(string $codiceLegacy): ?string
    {
        $up = strtoupper(trim($codiceLegacy));
        return preg_match(self::REGEX_LEGACY, $up) === 1 ? $up : null;
    }

    /**
     * Estrae il numero (5 cifre) dal codice canonico.
     */
    public function numero(): int
    {
        // F-12345-A → 12345
        return (int) substr($this->valore, 2, 5);
    }

    /**
     * Estrae il suffisso revisione (lettera maiuscola).
     */
    public function revisione(): string
    {
        // F-12345-A → A
        return substr($this->valore, -1);
    }

    public function __toString(): string
    {
        return $this->valore;
    }
}
