<?php

declare(strict_types=1);

namespace App\Modules\Commessa\ValueObjects;

use InvalidArgumentException;

/**
 * Identificativo di una commessa Onda.
 *
 * Formato canonico: "0067164-26"
 *   - 7 cifre numero (zero-padded)
 *   - "-"
 *   - 2 cifre anno (yy)
 *
 * @example
 *   $cc = CodiceCommessa::daStringa('0067164-26');
 *   echo $cc->numero;      // "0067164"
 *   echo $cc->anno;        // 26
 *   echo $cc->formato();   // "0067164-26"
 */
final class CodiceCommessa
{
    public function __construct(
        public readonly string $numero,
        public readonly int $anno,
    ) {
        if (!preg_match('/^\d{1,7}$/', $numero)) {
            throw new InvalidArgumentException(
                "CodiceCommessa: numero non valido '{$numero}' (atteso 1-7 cifre)."
            );
        }

        if ($anno < 0 || $anno > 99) {
            throw new InvalidArgumentException(
                "CodiceCommessa: anno fuori range (0-99): {$anno}."
            );
        }
    }

    /**
     * Costruisce dal formato canonico "NNNNNNN-YY".
     *
     * @example CodiceCommessa::daStringa('0067164-26')
     */
    public static function daStringa(string $cod): self
    {
        $cod = trim($cod);

        if (!preg_match('/^(\d{1,7})-(\d{2})$/', $cod, $m)) {
            throw new InvalidArgumentException(
                "CodiceCommessa: formato non riconosciuto '{$cod}' (atteso 'NNNNNNN-YY')."
            );
        }

        return new self(
            numero: str_pad($m[1], 7, '0', STR_PAD_LEFT),
            anno: (int) $m[2],
        );
    }

    /**
     * Rappresentazione canonica usata in DB e UI ("0067164-26").
     */
    public function formato(): string
    {
        return sprintf('%s-%02d', $this->numero, $this->anno);
    }

    public function __toString(): string
    {
        return $this->formato();
    }
}
