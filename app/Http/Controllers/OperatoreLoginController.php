<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Operatore;
use App\Models\OperatoreToken;

class OperatoreLoginController extends Controller
{
    // Solo per login via browser
    public function form()
    {
        return view('operatore.login');
    }

    // Login API e web
    public function login(Request $request)
    {
        $codice = $request->input('codice_operatore');

        $operatore = Operatore::where('codice_operatore', $codice)->first();

        if (!$operatore) {
            return response()->json([
                'success' => false,
                'messaggio' => 'Operatore non trovato'
            ]);
        }

        // Genera token random
        $token = bin2hex(random_bytes(16));

        // Salva token nella tabella operatore_tokens
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
                'reparto' => $operatore->reparto
            ],
            'redirect' => route('operatore.dashboard')
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        $operatore = $request->attributes->get('operatore');

        if($operatore){
            $operatore->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'redirect' => route('operatore.login')
        ]);
    }
}