<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Operatore;
use Illuminate\Support\Facades\Auth;

class OperatoreLoginController extends Controller
{
    // Mostra form login
    public function form()
    {
        return view('operatore.login');
    }

    // Login operatore
    public function login(Request $request)
    {
        $request->validate([
            'codice_operatore' => 'required|string'
        ]);

        $operatore = Operatore::where('codice_operatore', $request->codice_operatore)
                                ->where('attivo', 1)
                
                                ->first();

        if (!$operatore) {
            return back()->withErrors(['codice_operatore' => 'Operatore non trovato o inattivo']);
        }

        // Login con guard operatore
        Auth::guard('operatore')->login($operatore);

        // Set sessione
        $request->session()->put([
            'operatore_id' => $operatore->id,
            'operatore_nome' => $operatore->nome,
            'operatore_reparto' => $operatore->reparto,
            'operatore_ruolo' => $operatore->ruolo
        ]);
if ($operatore->ruolo === 'owner') {
            return redirect()->route('owner.dashboard');
        } else {
            return redirect()->route('operatore.dashboard');
        }
    }

    // Logout
    public function logout(Request $request)
    {
        Auth::guard('operatore')->logout();
        $request->session()->flush();
        return redirect()->route('operatore.login');
    }
}