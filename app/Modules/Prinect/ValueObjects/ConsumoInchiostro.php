<?php

declare(strict_types=1);

namespace App\Modules\Prinect\ValueObjects;

use InvalidArgumentException;

/**
 * Consumo inchiostri di un workstep, in grammi.
 *
 * Quadricromia esplicita (CMYK) + dizionario di colori speciali (spot)
 * per nome (es. "Pantone 286 C", "WhiteOpaque").
 *
 * Mappa lo schema `inkConsumptions` esposto dall'API Prinect:
 *  [{ inkName: "Cyan", consumptionGrams: 12.4 }, ...]
 */
final class ConsumoInchiostro
{
    /**
     * @param array<string,float> $spot mappa nomeColore -> grammi (>=0)
     */
    public function __construct(
        public readonly float $cyanG,
        public readonly float $magentaG,
        public readonly float $yellowG,
        public readonly float $blackG,
        public readonly array $spot = [],
    ) {
        foreach (['cyanG' => $cyanG, 'magentaG' => $magentaG, 'yellowG' => $yellowG, 'blackG' => $blackG] as $name => $val) {
            if ($val < 0) {
                throw new InvalidArgumentException("ConsumoInchiostro: {$name} negativo.");
            }
        }
        foreach ($spot as $color => $val) {
            if (!is_string($color) || $color === '') {
                throw new InvalidArgumentException('ConsumoInchiostro: nome colore spot non valido.');
            }
            if (!is_numeric($val) || $val < 0) {
                throw new InvalidArgumentException("ConsumoInchiostro: spot '{$color}' negativo o non numerico.");
            }
        }
    }

    public static function zero(): self
    {
        return new self(0.0, 0.0, 0.0, 0.0, []);
    }

    /**
     * Costruzione dal payload API Prinect (chiave inkConsumptions).
     *
     * @param array<int,array<string,mixed>> $inkConsumptions
     */
    public static function daApi(array $inkConsumptions): self
    {
        $c = 0.0; $m = 0.0; $y = 0.0; $k = 0.0;
        $spot = [];

        foreach ($inkConsumptions as $row) {
            $name  = (string) ($row['inkName'] ?? '');
            $grams = (float)  ($row['consumptionGrams'] ?? 0);

            switch (strtolower($name)) {
                case 'cyan':    $c += $grams; break;
                case 'magenta': $m += $grams; break;
                case 'yellow':  $y += $grams; break;
                case 'black':
                case 'key':     $k += $grams; break;
                default:
                    if ($name !== '') {
                        $spot[$name] = ($spot[$name] ?? 0.0) + $grams;
                    }
            }
        }

        return new self($c, $m, $y, $k, $spot);
    }

    public function totaleQuadricromiaG(): float
    {
        return round($this->cyanG + $this->magentaG + $this->yellowG + $this->blackG, 3);
    }

    public function totaleSpotG(): float
    {
        return round(array_sum($this->spot), 3);
    }

    public function totaleG(): float
    {
        return round($this->totaleQuadricromiaG() + $this->totaleSpotG(), 3);
    }
}
