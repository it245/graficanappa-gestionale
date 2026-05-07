<?php

declare(strict_types=1);

namespace App\Modules\Carta\Enums;

/**
 * Tipologia di superficie/finitura della carta.
 *
 * Influenza la scelta dell'inchiostro, l'asciugatura e la macchina da stampa
 * (es: SoftTouch e Vellutata non sono compatibili con UV diretto).
 */
enum TipoSuperficie: string
{
    case Lucida = 'LUCIDA';
    case Opaca = 'OPACA';
    case Satinata = 'SATINATA';
    case Ruvida = 'RUVIDA';
    case Vellutata = 'VELLUTATA';
    case SoftTouch = 'SOFT_TOUCH';
    case Metallica = 'METALLICA';

    /**
     * Risolve una superficie da una stringa libera. Ritorna Opaca se sconosciuta.
     */
    public static function daStringa(string $valore): self
    {
        $up = strtoupper(trim(str_replace([' ', '-'], '_', $valore)));

        foreach (self::cases() as $c) {
            if ($c->value === $up) {
                return $c;
            }
        }

        return self::Opaca;
    }

    /**
     * Indica se la superficie richiede attenzione particolare in stampa
     * (es. inchiostri specifici, no UV).
     */
    public function richiedeAttenzioneStampa(): bool
    {
        return match ($this) {
            self::SoftTouch, self::Vellutata, self::Metallica => true,
            default => false,
        };
    }
}
