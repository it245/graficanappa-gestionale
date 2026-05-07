<?php

declare(strict_types=1);

namespace App\Modules\Fustelle\ValueObjects;

use InvalidArgumentException;

/**
 * Dimensione fisica fustella: larghezza × altezza (mm) + spessore (mm).
 *
 * Esempio: 720 × 510 × 0.71 mm (BOBST formato 72x51, lama std).
 */
final readonly class DimensioneFustella
{
    public function __construct(
        public int $larghezzaMm,
        public int $altezzaMm,
        public float $spessoreMm,
    ) {
        if ($larghezzaMm <= 0 || $altezzaMm <= 0) {
            throw new InvalidArgumentException(
                "Dimensioni non valide: {$larghezzaMm}x{$altezzaMm}mm"
            );
        }
        if ($spessoreMm <= 0 || $spessoreMm > 50) {
            throw new InvalidArgumentException(
                "Spessore fuori range (0-50mm): {$spessoreMm}mm"
            );
        }
    }

    /**
     * Parsing rapido da stringa "LxA[xS]" oppure "L x A [x S]".
     *
     * Esempi:
     *   "720x510x0.71"    → 720, 510, 0.71
     *   "720 x 510"       → 720, 510, 0.71 (spessore default)
     *
     * @throws InvalidArgumentException
     */
    public static function daStringa(string $valore, float $spessoreDefault = 0.71): self
    {
        $v = strtolower(str_replace(' ', '', trim($valore)));

        if (preg_match('/^(\d+)x(\d+)(?:x([\d.]+))?$/', $v, $m) !== 1) {
            throw new InvalidArgumentException(
                "Formato dimensione non riconosciuto: '{$valore}' (atteso 'LxA' o 'LxAxS')"
            );
        }

        return new self(
            larghezzaMm: (int) $m[1],
            altezzaMm: (int) $m[2],
            spessoreMm: isset($m[3]) ? (float) $m[3] : $spessoreDefault,
        );
    }

    /**
     * Area in mm² (utile per stimare costo lama).
     */
    public function areaMm2(): int
    {
        return $this->larghezzaMm * $this->altezzaMm;
    }

    /**
     * Verifica se la fustella entra in un foglio macchina.
     */
    public function entraIn(int $foglioLarghezzaMm, int $foglioAltezzaMm): bool
    {
        // Considera anche orientamento ruotato.
        $entraDritta = $this->larghezzaMm <= $foglioLarghezzaMm
            && $this->altezzaMm <= $foglioAltezzaMm;
        $entraGirata = $this->altezzaMm <= $foglioLarghezzaMm
            && $this->larghezzaMm <= $foglioAltezzaMm;

        return $entraDritta || $entraGirata;
    }

    public function __toString(): string
    {
        return sprintf(
            '%d×%dmm sp.%smm',
            $this->larghezzaMm,
            $this->altezzaMm,
            rtrim(rtrim(number_format($this->spessoreMm, 2, '.', ''), '0'), '.')
        );
    }
}
