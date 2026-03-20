<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushController extends Controller
{
    /**
     * Registra una push subscription (chiamata dal browser via JS).
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string|max:500',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $operatoreId = $request->attributes->get('operatore_id') ?? session('operatore_id');
        $operatore = $request->attributes->get('operatore');
        $ruolo = $operatore->ruolo ?? session('operatore_ruolo') ?? 'operatore';

        PushSubscription::updateOrCreate(
            ['endpoint' => $request->input('endpoint')],
            [
                'operatore_id' => $operatoreId,
                'p256dh_key' => $request->input('keys.p256dh'),
                'auth_token' => $request->input('keys.auth'),
                'ruolo' => $ruolo,
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Rimuovi subscription (quando l'utente disabilita le notifiche).
     */
    public function unsubscribe(Request $request)
    {
        PushSubscription::where('endpoint', $request->input('endpoint'))->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Restituisce la chiave pubblica VAPID per il client.
     */
    public function vapidKey()
    {
        return response()->json([
            'publicKey' => config('webpush.public_key'),
        ]);
    }
}
