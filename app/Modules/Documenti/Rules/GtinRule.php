<?php

declare(strict_types=1);

namespace App\Modules\Documenti\Rules;

final class GtinRule
{
    /**
     * Genera GTIN a 14 cifre per datamatrix etichetta.
     * Layout: 'A' (literal) + hash 5 cifre del codice articolo + qta 8 cifre zero-padded.
     * Convenzione interna Grafica Nappa.
     */
    public function genera(string $codArt, int $qta): string
    {
        if ($qta < 0 || $qta > 99_999_999) {
            throw new \InvalidArgumentException("Quantita fuori range (0-99999999): {$qta}");
        }

        $codNorm = strtoupper(trim($codArt));
        $hash = abs(crc32($codNorm)) % 100_000;
        $hashPad = str_pad((string) $hash, 5, '0', STR_PAD_LEFT);
        $qtaPad = str_pad((string) $qta, 8, '0', STR_PAD_LEFT);

        return 'A' . $hashPad . $qtaPad;
    }

    public function valida(string $gtin): bool
    {
        return preg_match('/^A\d{13}$/', $gtin) === 1;
    }

    public function estraeQuantita(string $gtin): int
    {
        if (!$this->valida($gtin)) {
            throw new \InvalidArgumentException("GTIN non valido: {$gtin}");
        }

        return (int) substr($gtin, 6, 8);
    }
}
