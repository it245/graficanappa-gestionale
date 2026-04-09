<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\OperatoreToken;
use App\Models\Reparto;

class MagazzinoAuth
{
    /**
     * Magazzino: accessibile a owner, admin, oppure operatori del reparto spedizione.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedRoles = ['owner', 'owner_readonly', 'admin'];

        // 1. Prova token
        $token = $request->query('op_token') ?? $request->header('X-Op-Token');

        if ($token) {
            $record = OperatoreToken::valido()->where('token', $token)->with('operatore.reparti')->first();
            if ($record && $record->operatore && $record->operatore->attivo) {
                $op = $record->operatore;

                if (in_array($op->ruolo, $allowedRoles) || self::haRepartoSpedizione($op)) {
                    $request->attributes->set('operatore', $op);
                    $request->attributes->set('operatore_id', $op->id);
                    $request->attributes->set('operatore_nome', $op->nome);
                    $request->attributes->set('operatore_ruolo', $op->ruolo);
                    $request->attributes->set('op_token', $token);
                    return $next($request);
                }
            }
        }

        // 2. Fallback sessione
        $ruolo = session('operatore_ruolo');
        if (in_array($ruolo, $allowedRoles)) {
            return $next($request);
        }

        // Sessione: controlla reparto
        $opId = session('operatore_id');
        if ($opId) {
            $op = \App\Models\Operatore::with('reparti')->find($opId);
            if ($op && self::haRepartoSpedizione($op)) {
                $request->attributes->set('operatore', $op);
                return $next($request);
            }
        }

        abort(403, 'Non hai i permessi per accedere al magazzino.');
    }

    private static function haRepartoSpedizione($operatore): bool
    {
        $repartoSpedizione = Reparto::where('nome', 'spedizione')->first();
        if (!$repartoSpedizione) return false;

        // Controlla reparto_id diretto o relazione many-to-many
        if ($operatore->reparto_id == $repartoSpedizione->id) return true;

        return $operatore->reparti->contains('id', $repartoSpedizione->id);
    }
}
