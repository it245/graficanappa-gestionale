<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione "aggiungi riga manuale" dalla dashboard owner.
 *
 * Inserisce un nuovo ordine + ordine_fase. Valida:
 * - commessa: stringa breve (verrà arricchita con suffisso anno dal controller).
 * - fase_catalogo_id: facoltativo ma se presente deve esistere.
 * - qta_richiesta / priorita: numerici non negativi.
 * - um: codice unità di misura corto (FG, PZ, KG, ecc.).
 */
class OwnerAggiungiRigaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'commessa'                => 'bail|required|string|max:50|regex:/^[A-Za-z0-9._\-\/]+$/',
            'fase_catalogo_id'        => 'nullable|integer|exists:fasi_catalogo,id',
            'cliente_nome'            => 'nullable|string|max:255',
            'cod_art'                 => 'nullable|string|max:100',
            'descrizione'             => 'nullable|string|max:1000',
            'qta_richiesta'           => 'nullable|numeric|min:0|max:99999999',
            'um'                      => 'nullable|string|max:10',
            'data_prevista_consegna'  => 'nullable|date',
            'priorita'                => 'nullable|integer|min:0|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'commessa.required' => 'Commessa obbligatoria.',
            'commessa.regex'    => 'Commessa contiene caratteri non ammessi.',
            'fase_catalogo_id.exists' => 'Fase catalogo non valida.',
        ];
    }
}
