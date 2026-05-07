<?php

declare(strict_types=1);

namespace App\Modules\Carta\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object immutabile che rappresenta un codice articolo Onda
 * nel formato `02W.MARCA.TIPO.GRAMMATURA.SEQ`.
 *
 * @example
 *  Input:  "02W.ALASKA.GC1.300.003"
 *  Parse:  prefisso=02W, marca=ALASKA, tipo=GC1, grammatura=300, sequenza=003
 *
 *  Regex: /^02W\.([A-Z0-9]+)\.([A-Z0-9]+)\.(\d{2,4})\.(\d{1,4})$/
 *   - segmento 1 (marca):       lettere/cifre maiuscole, es. ALASKA, FEDRI
 *   - segmento 2 (tipo):        lettere/cifre maiuscole, es. GC1, GC2, PAT
 *   - segmento 3 (grammatura):  intero 2-4 cifre (80-9999gr)
 *   - segmento 4 (sequenza):    intero 1-4 cifre (001, 003, ...)
 */
final readonly class CodiceArticoloOnda
{
    public const PREFISSO = '02W';
    public const REGEX = '/^02W\.([A-Z0-9]+)\.([A-Z0-9]+)\.(\d{2,4})\.(\d{1,4})$/';

    public function __construct(
        public string $marca,
        public string $tipo,
        public int $grammatura,
        public int $sequenza,
        private string $stringaOriginale,
    ) {
    }

    /**
     * Crea un VO da una stringa cod_art Onda.
     *
     * @example
     *   CodiceArticoloOnda::daStringa('02W.ALASKA.GC1.300.003')
     *     // → marca=ALASKA, tipo=GC1, grammatura=300, sequenza=3
     *
     * @throws InvalidArgumentException se il formato non è valido.
     */
    public static function daStringa(string $cod): self
    {
        $cod = trim(strtoupper($cod));

        if (preg_match(self::REGEX, $cod, $m) !== 1) {
            throw new InvalidArgumentException(
                "Codice articolo Onda non valido: '{$cod}'. "
                . "Formato atteso: 02W.MARCA.TIPO.GRAMMATURA.SEQ"
            );
        }

        return new self(
            marca: $m[1],
            tipo: $m[2],
            grammatura: (int) $m[3],
            sequenza: (int) $m[4],
            stringaOriginale: $cod,
        );
    }

    /**
     * Tenta il parse senza lanciare eccezioni. Ritorna null se non valido.
     */
    public static function provaDaStringa(string $cod): ?self
    {
        try {
            return self::daStringa($cod);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * Ritorna la stringa originale (eventualmente normalizzata in maiuscolo).
     */
    public function formato(): string
    {
        return $this->stringaOriginale;
    }

    /**
     * Verifica che il VO sia valido (grammatura nel range tipico 40-600gr).
     */
    public function eValido(): bool
    {
        return $this->grammatura >= 40
            && $this->grammatura <= 600
            && $this->marca !== ''
            && $this->tipo !== ''
            && $this->sequenza >= 0;
    }

    /**
     * Restituisce la stringa originale.
     */
    public function __toString(): string
    {
        return $this->stringaOriginale;
    }
}
