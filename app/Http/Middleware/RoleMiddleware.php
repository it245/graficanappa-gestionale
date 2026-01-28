<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleTokenMiddleware
{
    public function handle(Request $request, Closure $next, string $ruolo)
    {
        $operatore = $request->attributes->get('operatore');

        if (!$operatore || $operatore->ruolo !== $ruolo) {
            return response()->json([
                'success' => false,
                'messaggio' => 'Accesso negato: ruolo richiesto '.$ruolo
            ], 403);
        }

        return $next($request);
    }
}