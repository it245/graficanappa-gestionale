<?php

declare(strict_types=1);

namespace App\Modules\Carta\Enums;

/**
 * Famiglie carta utilizzate in Grafica Nappa.
 *
 * Allineate ai codici Onda (segmento TIPO del cod_art).
 * GC1 = cartoncino vergine, GC2 = cartoncino doppio strato (riciclato),
 * Patinata/Usomano = carte da stampa standard,
 * Sirio/Tintoretto/Acquerello/Materica = carte speciali Fedrigoni,
 * Adesivo = carte adesive, PVC = supporti plastici,
 * Microonda/Tela = supporti per packaging,
 * Speciale/Altro = catch-all per articoli fuori catalogo.
 */
enum FamigliaCarta: string
{
    case GC1 = 'GC1';
    case GC2 = 'GC2';
    case Patinata = 'PATINATA';
    case Usomano = 'USOMANO';
    case Sirio = 'SIRIO';
    case Tintoretto = 'TINTORETTO';
    case Acquerello = 'ACQUERELLO';
    case Materica = 'MATERICA';
    case Adesivo = 'ADESIVO';
    case PVC = 'PVC';
    case Microonda = 'MICROONDA';
    case Tela = 'TELA';
    case Speciale = 'SPECIALE';
    case Altro = 'ALTRO';

    /**
     * Risolve una famiglia da una stringa libera (case-insensitive).
     * Ritorna Altro se non trovata.
     */
    public static function daStringa(string $valore): self
    {
        $up = strtoupper(trim($valore));

        foreach (self::cases() as $c) {
            if ($c->value === $up) {
                return $c;
            }
        }

        return self::Altro;
    }

    /**
     * Indica se la famiglia è un cartoncino (rigido, >200gr tipico).
     */
    public function eCartoncino(): bool
    {
        return match ($this) {
            self::GC1, self::GC2 => true,
            default => false,
        };
    }

    /**
     * Indica se la famiglia è una carta speciale Fedrigoni o simile.
     */
    public function eSpecialeFedrigoni(): bool
    {
        return match ($this) {
            self::Sirio, self::Tintoretto, self::Acquerello, self::Materica => true,
            default => false,
        };
    }
}
