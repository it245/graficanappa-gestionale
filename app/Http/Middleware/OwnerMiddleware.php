<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OwnerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $operatore = $request->attributes->get('operatore');

        if (!$operatore || $operatore->ruolo !== 'owner') {
            return response()->json(['success' => false, 'messaggio' => 'Accesso negato'], 403);
        }

        return $next($request);
    }
}