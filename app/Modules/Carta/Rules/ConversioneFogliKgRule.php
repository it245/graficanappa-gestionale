<?php

declare(strict_types=1);

namespace App\Modules\Carta\Rules;

use App\Modules\Carta\ValueObjects\Formato;
use InvalidArgumentException;

/**
 * Conversioni tra peso (kg) e numero di fogli per un dato formato e grammatura.
 *
 * Formula peso_foglio (kg):
 *   peso_foglio_kg = (lunghezza_m * larghezza_m * grammatura_g_m2) / 1000
 *
 * @example
 *   $f = new Formato(1000, 700); // 70x100 cm
 *   $r = new ConversioneFogliKgRule();
 *   $r->fogliDaKg(300, $f, 100.0); // ≈ 476 fogli
 *   $r->kgDaFogli(300, $f, 1000);  // ≈ 210.0 kg
 */
final class ConversioneFogliKgRule
{
    /**
     * Calcola il peso (kg) di un singolo foglio.
     */
    public function pesoFoglioKg(int $grammatura, Formato $f): float
    {
        $this->validaGrammatura($grammatura);

        $lunghezza_m = $f->lunghezza_mm / 1000.0;
        $larghezza_m = $f->larghezza_mm / 1000.0;

        return ($lunghezza_m * $larghezza_m * $grammatura) / 1000.0;
    }

    /**
     * Numero di fogli ottenibili da una quantità in kg (arrotondato per difetto).
     *
     * @throws InvalidArgumentException se grammatura o kg non validi
     */
    public function fogliDaKg(int $grammatura, Formato $f, float $kg): int
    {
        if ($kg < 0) {
            throw new InvalidArgumentException("kg non può essere negativo: {$kg}");
        }

        $peso = $this->pesoFoglioKg($grammatura, $f);
        if ($peso <= 0.0) {
            return 0;
        }

        return (int) floor($kg / $peso);
    }

    /**
     * Peso (kg) di N fogli.
     */
    public function kgDaFogli(int $grammatura, Formato $f, int $fogli): float
    {
        if ($fogli < 0) {
            throw new InvalidArgumentException("fogli non può essere negativo: {$fogli}");
        }

        return round($this->pesoFoglioKg($grammatura, $f) * $fogli, 3);
    }

    private function validaGrammatura(int $g): void
    {
        if ($g < 40 || $g > 600) {
            throw new InvalidArgumentException(
                "Grammatura fuori range plausibile (40-600gr): {$g}"
            );
        }
    }
}
