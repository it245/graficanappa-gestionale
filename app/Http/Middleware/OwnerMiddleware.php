<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OwnerMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Controlla se esiste la sessione e se il ruolo è owner
        if (session('operatore_ruolo') === 'owner') {
            return $next($request);
        }

        abort(403, 'Accesso negato.'); 
    }
}