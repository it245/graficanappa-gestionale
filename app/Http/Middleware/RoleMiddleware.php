<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!auth()->check() || auth()->user()->ruolo !== $role) {
            abort(403, 'Accesso negato');
        }

        return $next($request);
    }
}