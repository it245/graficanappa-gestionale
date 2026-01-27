<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Operatore;
use Symfony\Component\HttpFoundation\Response;

class OwnerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $operatoreId = $request->session()->get('operatore_id');
        $sessionToken = $request->session()->get('session_token');

        if (!$operatoreId || !$sessionToken) {
            return redirect()->route('operatore.login');
        }

        $operatore = Operatore::find($operatoreId);

        if (!$operatore || $operatore->ruolo !== 'owner' || $operatore->session_token !== $sessionToken) {
            $request->session()->flush();
            abort(403, 'Accesso negato.');
        }
        return $next($request);
    }
}