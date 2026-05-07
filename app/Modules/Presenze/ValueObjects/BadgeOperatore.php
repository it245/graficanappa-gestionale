<?php

declare(strict_types=1);

namespace App\Modules\Presenze\ValueObjects;

/**
 * Badge / matricola operatore.
 *
 * Convenzione NetTime Grafica Nappa: la matricola in `nettime_anagrafica`
 * è una stringa di 6 cifre (zero-padded). Il file TIMBRACP.BKP contiene
 * 8 cifne con leading zeros — `SyncPresenze::syncTimbrature()` la
 * normalizza a 6 cifre.
 *
 * VO immutabile: validazione formato in costruzione, niente setter.
 */
final readonly class BadgeOperatore
{
    private const PATTERN = '/^\d{4,8}$/';
    private const PADDED_LENGTH = 6;

    public function __construct(public string $matricola)
    {
        if (!preg_match(self::PATTERN, $this->matricola)) {
            throw new \InvalidArgumentException(
                "Matricola NetTime non valida: '{$this->matricola}' (atteso 4-8 cifre)"
            );
        }
    }

    /**
     * Crea un badge normalizzato (6 cifre zero-padded) come da
     * convenzione `nettime_anagrafica.matricola`.
     */
    public static function da(string|int $raw): self
    {
        $raw = ltrim((string) $raw, '0');
        if ($raw === '') {
            $raw = '0';
        }
        $padded = str_pad($raw, self::PADDED_LENGTH, '0', STR_PAD_LEFT);
        return new self($padded);
    }

    public function equals(self $other): bool
    {
        return $this->matricola === $other->matricola;
    }

    public function __toString(): string
    {
        return $this->matricola;
    }
}
