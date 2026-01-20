<?php

namespace App\Http\Controllers;

use App\Models\Operatore;
use Illuminate\Http\Request;

class OperatoreLoginController extends Controller
{

public function form()
{
    return view('operatore.login');
}
    // Login operatore
    public function login(Request $request)
    {
        $request->validate([
            'codice_operatore' => 'required|string',
        ]);

        $operatore = Operatore::where('codice_operatore', $request->codice_operatore)
            ->where('attivo', 1)
            ->first();

        if (!$operatore) {
            return back()->withErrors([
                'codice_operatore' => 'Codice operatore non valido o operatore disattivo',
            ]);
        }

        // Salvo dati in sessione
        session([
            'operatore_id' => $operatore->id,
            'operatore_nome' => $operatore->nome,
            'operatore_reparto' => $operatore->reparto,
        ]);

        return redirect()->route('operatore.dashboard');
    }

    // Logout operatore
    public function logout()
    {
        session()->forget([
            'operatore_id',
            'operatore_nome',
            'operatore_reparto',
        ]);

        return redirect()->route('operatore.login');
    }
}
