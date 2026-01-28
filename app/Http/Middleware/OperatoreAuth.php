<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\OperatoreToken;

class OperatoreAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Prende il token dall'header personalizzato o da Authorization Bearer
        $token = $request->header('X-Operatore-Token') ?? $request->bearerToken();

        if (!$token) {
            // Se non c'è token, reindirizza al login
            return redirect()->route('operatore.login');
        }

        // Recupera il token e l'operatore associato
        $record = OperatoreToken::with('operatore')->where('token', $token)->first();

        if (!$record || !$record->operatore) {
            // Token non valido: cancelliamo eventuali residui e reindirizziamo
            return redirect()->route('operatore.login')->withErrors([
                'sessione' => 'Token non valido o scaduto, effettua nuovamente il login.'
            ]);
        }

        // Imposta l'operatore nella request
        $request->attributes->set('operatore', $record->operatore);

        return $next($request);
    }
}