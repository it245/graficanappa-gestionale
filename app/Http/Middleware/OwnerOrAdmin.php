<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\OperatoreToken;

class OwnerOrAdmin
{
    public function handle(Request $request, Closure $next, ...$extraRoles): Response
    {
        $allowedRoles = array_merge(['owner', 'owner_readonly', 'admin'], $extraRoles);

        // 1. Prova token
        $token = $request->query('op_token') ?? $request->header('X-Op-Token');

        if ($token) {
            $record = OperatoreToken::valido()->where('token', $token)->with('operatore')->first();
            if ($record && $record->operatore && in_array($record->operatore->ruolo, $allowedRoles)) {
                $op = $record->operatore;
                $request->attributes->set('operatore', $op);
                $request->attributes->set('operatore_id', $op->id);
                $request->attributes->set('operatore_nome', $op->nome);
                $request->attributes->set('operatore_ruolo', $op->ruolo);
                $request->attributes->set('op_token', $token);
                return $next($request);
            }
        }

        // 2. Fallback sessione
        $ruolo = session('operatore_ruolo');
        if (in_array($ruolo, $allowedRoles)) {
            return $next($request);
        }

        abort(403, 'Accesso negato.');
    }
}
