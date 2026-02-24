<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\OperatoreToken;

class OperatoreAuth
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Prova token: query param → header → POST param
        $token = $request->query('op_token') ?? $request->header('X-Op-Token');

        if ($token) {
            $record = OperatoreToken::valido()->where('token', $token)->with('operatore')->first();
            if ($record && $record->operatore) {
                $op = $record->operatore;
                $request->attributes->set('operatore', $op);
                $request->attributes->set('operatore_id', $op->id);
                $request->attributes->set('operatore_nome', $op->nome);
                $request->attributes->set('operatore_ruolo', $op->ruolo);
                $request->attributes->set('op_token', $token);
                return $next($request);
            }

            // Token non valido: redirect login
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'messaggio' => 'Token non valido o scaduto'], 401);
            }
            return redirect()->route('operatore.login');
        }

        // 2. Fallback sessione
        if ($request->session()->has('operatore_id')) {
            $request->attributes->set('operatore_id', $request->session()->get('operatore_id'));
            $request->attributes->set('operatore_nome', $request->session()->get('operatore_nome'));
            $request->attributes->set('operatore_ruolo', $request->session()->get('operatore_ruolo'));
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'messaggio' => 'Sessione scaduta'], 401);
        }
        return redirect()->route('operatore.login');
    }
}
