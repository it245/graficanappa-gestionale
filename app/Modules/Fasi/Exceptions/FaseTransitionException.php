<?php

declare(strict_types=1);

namespace App\Modules\Fasi\Exceptions;

use DomainException;

/**
 * Sollevata quando una transizione di stato viola la macchina a stati.
 */
class FaseTransitionException extends DomainException
{
    public static function nonAmmessa(int|string $da, int|string $a): self
    {
        return new self(sprintf(
            'Transizione non ammessa: %s -> %s',
            self::format($da),
            self::format($a),
        ));
    }

    public static function pausaNonConsentita(int|string $statoCorrente): self
    {
        return new self(sprintf(
            'Pausa non consentita: la fase deve essere in stato Avviata (2), trovato %s',
            self::format($statoCorrente),
        ));
    }

    public static function ripresaNonConsentita(int|string $statoCorrente): self
    {
        return new self(sprintf(
            'Ripresa non consentita: la fase non è in pausa, stato corrente %s',
            self::format($statoCorrente),
        ));
    }

    private static function format(int|string $stato): string
    {
        return is_string($stato) ? "'{$stato}'" : (string) $stato;
    }
}
