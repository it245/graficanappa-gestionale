<?php

declare(strict_types=1);

namespace App\Modules\Carta\ValueObjects;

/**
 * Value Object immutabile per i formati carta (sempre in millimetri).
 *
 * Convenzione: lunghezza = lato maggiore, larghezza = lato minore.
 * La sigla standard è "LxW" in centimetri (es. "70x100", "33x48").
 *
 * @example
 *   Formato::daStringa('70x100') // → 700mm x 1000mm (interpretato come cm)
 *   Formato::daStringa('700x1000') // → 700mm x 1000mm (interpretato come mm)
 *   $f->area_m2() // 0.70
 *   $f->siglaStandard() // "70x100"
 */
final readonly class Formato
{
    public function __construct(
        public int $lunghezza_mm,
        public int $larghezza_mm,
    ) {
    }

    /**
     * Parse di una stringa formato. Accetta:
     *   - "70x100" o "70X100" (interpreta come cm → mm)
     *   - "700x1000" (interpreta come mm)
     *   - "33x48", "48x33", "51x36"
     *   - separatori: x, X, *, ×, spazi
     *
     * Regex: /^\s*(\d{2,4})\s*[xX*×]\s*(\d{2,4})\s*$/
     *
     * Ritorna null se il parsing fallisce.
     *
     * @example
     *   Formato::daStringa('70x100')   // 700×1000 mm
     *   Formato::daStringa('33 x 48')  // 330×480  mm
     *   Formato::daStringa('700x1000') // 700×1000 mm
     */
    public static function daStringa(string $f): ?self
    {
        $clean = str_replace(['×', '*'], 'x', trim($f));

        if (preg_match('/^\s*(\d{2,4})\s*[xX]\s*(\d{2,4})\s*$/', $clean, $m) !== 1) {
            return null;
        }

        $a = (int) $m[1];
        $b = (int) $m[2];

        // Normalizza in mm: se entrambi <=200 si interpreta come cm.
        // Soglia 200 cm = 2000 mm = formato max plausibile per Grafica Nappa.
        if ($a <= 200 && $b <= 200) {
            $a *= 10;
            $b *= 10;
        }

        // lunghezza = lato maggiore
        $lunghezza = max($a, $b);
        $larghezza = min($a, $b);

        return new self($lunghezza, $larghezza);
    }

    /**
     * Area in metri quadri.
     */
    public function area_m2(): float
    {
        return ($this->lunghezza_mm / 1000.0) * ($this->larghezza_mm / 1000.0);
    }

    /**
     * Sigla standard in centimetri, es. "70x100".
     * Usa i lati arrotondati al cm.
     */
    public function siglaStandard(): string
    {
        $l = (int) round($this->lunghezza_mm / 10);
        $w = (int) round($this->larghezza_mm / 10);

        return "{$w}x{$l}";
    }

    /**
     * Indica se il formato corrisponde (entro tolleranza ±2mm) a un altro formato.
     */
    public function corrispondeA(self $altro, int $tolleranza_mm = 2): bool
    {
        return abs($this->lunghezza_mm - $altro->lunghezza_mm) <= $tolleranza_mm
            && abs($this->larghezza_mm - $altro->larghezza_mm) <= $tolleranza_mm;
    }

    public function __toString(): string
    {
        return $this->siglaStandard();
    }
}
