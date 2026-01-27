<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\OperatoreToken;

class OperatoreTokenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Prende il token dall'header personalizzato o da Authorization Bearer
        $token = $request->header('X-Operatore-Token') ?? $request->bearerToken();

        if (!$token) {
            return response()->json(['success' => false, 'messaggio' => 'Token mancante'], 401);
        }

        $record = OperatoreToken::with('operatore')->where('token', $token)->first();

        if (!$record || !$record->operatore) {
            return response()->json(['success' => false, 'messaggio' => 'Token non valido'], 401);
        }

        // Imposta l'operatore nella request
        $request->attributes->set('operatore', $record->operatore);

        return $next($request);
    }
}