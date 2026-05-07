<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione login operatore (codice operatore alfanumerico).
 *
 * I codici operatore sono alfanumerici (vedi memoria progetto):
 * niente regola "numeric" o "digits". Limitiamo solo lunghezza
 * + carattere set ragionevole per chiudere SQLi/XSS.
 */
class OperatoreLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // alfanumerico + qualche separatore, max 50 caratteri
            'codice_operatore' => 'bail|required|string|max:50|regex:/^[A-Za-z0-9._\-]+$/',
        ];
    }

    public function messages(): array
    {
        return [
            'codice_operatore.required' => 'Inserisci il codice operatore.',
            'codice_operatore.regex'    => 'Codice operatore non valido.',
            'codice_operatore.max'      => 'Codice operatore troppo lungo.',
        ];
    }
}
