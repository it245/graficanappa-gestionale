<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione login admin.
 *
 * Limita lunghezza dei campi per:
 * - Mitigare brute force / payload abuse (max length).
 * - Evitare timing attack diversi su stringhe enormi.
 * - Bloccare input malformati prima di toccare il DB.
 *
 * NB: il controller decide il messaggio di errore generico
 * ("Credenziali non valide") per non favorire user enumeration.
 */
class AdminLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Rotta pubblica: nessuna logica di auth qui (la gestisce il controller).
        return true;
    }

    public function rules(): array
    {
        return [
            'codice_operatore' => 'bail|required|string|max:100',
            'password'         => 'bail|required|string|max:200',
        ];
    }

    public function messages(): array
    {
        return [
            'codice_operatore.required' => 'Codice operatore richiesto.',
            'codice_operatore.max'      => 'Codice operatore non valido.',
            'password.required'         => 'Password richiesta.',
            'password.max'              => 'Password non valida.',
        ];
    }
}
