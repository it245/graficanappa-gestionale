<?php

declare(strict_types=1);

namespace App\Modules\Fasi\Rules;

use App\Models\OrdineFase;

/**
 * Regole di guard per pausa/ripresa di una fase.
 *
 * Pausa  : ammessa solo se stato corrente == 2 (AVVIATA)
 * Ripresa: ammessa solo se stato corrente è una stringa di motivo pausa
 *          (qualsiasi string non numerica e diversa da 'EXT').
 */
final class PausaRule
{
    public static function canPausa(OrdineFase $fase): bool
    {
        $stato = $fase->stato;

        if (is_int($stato)) {
            return $stato === 2;
        }

        return is_string($stato) && ctype_digit($stato) && (int) $stato === 2;
    }

    public static function canRiprendi(OrdineFase $fase): bool
    {
        $stato = $fase->stato;

        if (! is_string($stato)) {
            return false;
        }

        if (ctype_digit($stato)) {
            return false;
        }

        return strtoupper($stato) !== 'EXT';
    }
}
