<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\OperatoreToken;

class OperatoreTokenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Prende il token dall'header personalizzato o da Authorization Bearer
       $token = $request->header('X-Operatore-Token') ?? $request->bearerToken();
if (!$token) return response()->json(['success' => false, 'messaggio' => 'Token mancante'], 403);

$tokenRecord = OperatoreToken::where('token', $token)->first();
if (!$tokenRecord) return response()->json(['success' => false, 'messaggio' => 'Token non valido'], 403);

$request->attributes->set('operatore', $tokenRecord->operatore);
return $next($request);
    }
}