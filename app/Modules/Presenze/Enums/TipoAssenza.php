<?php

declare(strict_types=1);

namespace App\Modules\Presenze\Enums;

/**
 * Tipologie di assenza giustificata.
 *
 * SCIOPERO è incluso in vista della procedura sindacale art.4
 * Statuto Lavoratori — il MES traccia produttività individuale e
 * deve poter distinguere uno sciopero da una malattia per le
 * comunicazioni RSU/RSA.
 */
enum TipoAssenza: string
{
    case Ferie     = 'FERIE';
    case Malattia  = 'MALATTIA';
    case Permesso  = 'PERMESSO';
    case Sciopero  = 'SCIOPERO';
    case Maternita = 'MATERNITA';
    case Infortunio = 'INFORTUNIO';
    case ROL       = 'ROL'; // riposi compensativi

    public function label(): string
    {
        return match ($this) {
            self::Ferie      => 'Ferie',
            self::Malattia   => 'Malattia',
            self::Permesso   => 'Permesso',
            self::Sciopero   => 'Sciopero',
            self::Maternita  => 'Maternità',
            self::Infortunio => 'Infortunio',
            self::ROL        => 'ROL',
        };
    }

    /**
     * Le assenze retribuite contano nel monte ore mensile.
     */
    public function isRetribuita(): bool
    {
        return match ($this) {
            self::Sciopero => false,
            default        => true,
        };
    }
}
