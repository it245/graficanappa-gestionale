<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Operatore;
class OperatoreAuth
{
    public function handle(Request $request, Closure $next)
    {
         $operatoreId = $request->session()->get('operatore_id');
        $sessionToken = $request->session()->get('session_token');
       
        // Controlla se l'operatore è loggato in sessione
       if(!$operatoreId || !$sessionToken) {
           return redirect()->route('operatore.login');
    }
    $operatore = Operatore::find($operatoreId);
    if (!$operatore || $operatore->session_token !== $sessionToken) {
        $request->session()->flush();
        return redirect()->route('operatore.login')->withErrors(['sessione' => 'Sessione scaduta, effettua nuovamente il login.']);
}
        return $next($request);
    }
}