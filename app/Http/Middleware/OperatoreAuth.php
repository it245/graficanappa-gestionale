<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OperatoreAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Controlla se l'operatore è loggato in sessione
        if (!$request->session()->has('operatore_id')) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'messaggio' => 'Sessione scaduta'], 401);
            }
            return redirect()->route('operatore.login');
        }

        return $next($request);
    }
}