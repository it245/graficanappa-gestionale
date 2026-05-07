<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Aggiornamento inline di un singolo campo su ordine_fasi / ordini.
 *
 * Whitelist rigorosa di "campo": evita mass assignment e blocca update
 * di colonne arbitrarie (es. id, password, ruolo, flag interni).
 *
 * Il valore resta tipicamente "nullable" perché il controller fa il
 * cast/parsing successivo (date, numerici, ecc.); imponiamo però una
 * lunghezza massima per evitare payload abuse / XSS persistenti.
 */
class OwnerAggiornaCampoRequest extends FormRequest
{
    /** Campi modificabili sulla fase (ordine_fasi). */
    public const CAMPI_FASE = [
        'qta_prod', 'qta_fase', 'note', 'stato', 'data_inizio', 'data_fine',
        'ore', 'priorita', 'fase', 'esterno', 'reparto',
    ];

    /** Campi modificabili sull'ordine (ordini). */
    public const CAMPI_ORDINE = [
        'cliente_nome', 'cod_art', 'descrizione', 'qta_richiesta', 'um',
        'data_registrazione', 'data_prevista_consegna',
        'cod_carta', 'carta', 'qta_carta', 'UM_carta',
    ];

    public function authorize(): bool
    {
        // L'autorizzazione operativa è già nel middleware owner + denyIfReadonly().
        return true;
    }

    public function rules(): array
    {
        $whitelist = array_merge(self::CAMPI_FASE, self::CAMPI_ORDINE);

        return [
            'fase_id' => 'bail|required|integer|exists:ordine_fasi,id',
            'campo'   => ['bail', 'required', 'string', Rule::in($whitelist)],
            // Il valore può essere null (es. "-" per cancellare data) o stringa lunga (note).
            'valore'  => 'nullable|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'fase_id.exists' => 'Fase non trovata.',
            'campo.in'       => 'Campo non aggiornabile.',
            'valore.max'     => 'Valore troppo lungo (max 5000 caratteri).',
        ];
    }
}
