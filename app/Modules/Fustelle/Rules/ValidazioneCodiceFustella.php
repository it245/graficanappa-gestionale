<?php

declare(strict_types=1);

namespace App\Modules\Fustelle\Rules;

use App\Modules\Fustelle\ValueObjects\CodiceFustella;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regola di validazione Laravel per codici fustella canonici (F-NNNNN-X).
 *
 * Uso:
 *   $request->validate(['codice' => ['required', new ValidazioneCodiceFustella()]]);
 */
final class ValidazioneCodiceFustella implements ValidationRule
{
    public function __construct(
        private readonly bool $accettaLegacy = false,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail("Il campo {$attribute} deve essere una stringa.");
            return;
        }

        if (CodiceFustella::provaDaStringa($value) !== null) {
            return;
        }

        if ($this->accettaLegacy && CodiceFustella::daLegacy($value) !== null) {
            return;
        }

        $fail("Il campo {$attribute} non è un codice fustella valido (atteso F-NNNNN-X).");
    }
}
