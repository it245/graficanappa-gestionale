<?php

declare(strict_types=1);

namespace App\Modules\Presenze\Enums;

/**
 * Tipo di timbratura registrata.
 *
 * Mappa i due valori legacy ('E', 'U') in nettime_timbrature.verso
 * + estensioni semantiche (PAUSA, RIENTRO) per usi futuri quando il
 * NetTime esporrà i causali aggiuntivi (oggi sono inferiti dal gap
 * temporale tra E consecutive in PresenzeController::index).
 */
enum TipoTimbratura: string
{
    case In = 'IN';
    case Out = 'OUT';
    case Pausa = 'PAUSA';
    case Rientro = 'RIENTRO';

    /**
     * Mappa legacy NetTime ('E' = entrata, 'U' = uscita) → enum.
     */
    public static function daVerso(string $verso): self
    {
        return match (strtoupper($verso)) {
            'E' => self::In,
            'U' => self::Out,
            default => throw new \InvalidArgumentException("Verso NetTime non valido: {$verso}"),
        };
    }

    /**
     * Esporta verso il formato legacy ('E' / 'U'). Pausa e Rientro
     * sono mappati come Out/In rispettivamente per compat DB.
     */
    public function aVerso(): string
    {
        return match ($this) {
            self::In, self::Rientro => 'E',
            self::Out, self::Pausa  => 'U',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::In      => 'Ingresso',
            self::Out     => 'Uscita',
            self::Pausa   => 'Pausa',
            self::Rientro => 'Rientro',
        };
    }
}
