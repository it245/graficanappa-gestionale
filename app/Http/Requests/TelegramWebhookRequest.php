<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validazione webhook Telegram.
 *
 * La firma/secret URL viene già verificata nel controller con hash_equals().
 * Qui imponiamo una shape minima del payload per filtrare richieste random
 * o malevole (e bloccare body enormi che intaserebbero il queue/log).
 *
 * Telegram invia sempre uno di questi campi top-level:
 *   - message
 *   - edited_message
 *   - callback_query
 *   - channel_post
 *   - my_chat_member
 *   - ...
 *
 * Per non falsare il riconoscimento di tipi futuri, accettiamo qualsiasi
 * payload con `update_id` numerico, e validiamo la lunghezza testo se
 * presente.
 */
class TelegramWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'autorizzazione vera è hash_equals(secret) nel controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'update_id'          => 'bail|required|integer',
            // Tutti gli altri campi sono opzionali, ma se presenti devono essere array/stringa.
            'message'            => 'sometimes|array',
            'message.text'       => 'nullable|string|max:4096',          // limite Telegram
            'message.chat.id'    => 'sometimes|integer',
            'edited_message'     => 'sometimes|array',
            'callback_query'     => 'sometimes|array',
            'callback_query.data' => 'nullable|string|max:64',           // limite Telegram
            'channel_post'       => 'sometimes|array',
        ];
    }
}
