<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Operatore;
use App\Models\OperatoreToken;
use Illuminate\Support\Facades\Hash;

class OperatoreLoginController extends Controller
{
     public function form()
    {
        return view('operatore.login');
    }
   public function login(Request $request)
{
    $request->validate([
        'codice_operatore' => 'required|string'
    ]);

    $codice = strtolower($request->input('codice_operatore'));
    $operatore = Operatore::where('codice_operatore', $codice)->first();

    if (!$operatore) {
        return response()->json(['success' => false, 'messaggio' => 'Operatore non trovato']);
    }

    // Cancella token precedente per questo operatore (optional)
    $operatore->tokens()->delete();

    // Genera token nuovo
    $token = bin2hex(random_bytes(16));
    OperatoreToken::create([
        'operatore_id' => $operatore->id,
        'token' => $token
    ]);

    return response()->json([
        'success' => true,
        'token' => $token,
        'operatore' => [
            'id' => $operatore->id,
            'nome' => $operatore->nome,
            'ruolo' => $operatore->ruolo,
            'reparto' => $operatore->reparto,
        ]
    ]);
}

public function logout(Request $request)
{
    $token = $request->header('X-Operatore-Token') ?? $request->bearerToken();

    if ($token) {
        OperatoreToken::where('token', $token)->delete();
    }

    return response()->json(['success' => true]);
}
}