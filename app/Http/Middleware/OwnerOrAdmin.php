<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OwnerOrAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $ruolo = session('operatore_ruolo');

        if ($ruolo === 'owner' || $ruolo === 'admin') {
            return $next($request);
        }

        abort(403, 'Accesso negato.');
    }
}
