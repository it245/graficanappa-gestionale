<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Operatore;
use App\Models\OperatoreToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

        // Set sessione (fallback per compatibilita)
        $request->session()->put([
            'operatore_id' => $operatore->id,
            'operatore_nome' => $operatore->nome,
            'operatore_reparto' => $operatore->reparto,
            'operatore_ruolo' => $operatore->ruolo
        ]);

        // Genera token per autenticazione per-tab
        $token = Str::random(64);
        OperatoreToken::create([
            'operatore_id' => $operatore->id,
            'token' => $token,
            'expires_at' => now()->addHours(12),
        ]);

        if ($operatore->ruolo === 'owner') {
            return redirect()->route('owner.dashboard', ['op_token' => $token]);
        }

        // Redirect spedizione
        $repartiNomi = $operatore->reparti->pluck('nome')->map(fn($n) => strtolower($n))->toArray();
        if (in_array('spedizione', $repartiNomi)) {
            return redirect()->route('spedizione.dashboard', ['op_token' => $token]);
        }

        return redirect()->route('operatore.dashboard', ['op_token' => $token]);
    }

    // Logout
    public function logout(Request $request)
    {
        // Elimina token dal DB
        $token = $request->query('op_token') ?? $request->header('X-Op-Token') ?? $request->input('op_token');
        if ($token) {
            OperatoreToken::where('token', $token)->delete();
        }

        Auth::guard('operatore')->logout();
        $request->session()->flush();
        return redirect()->route('operatore.login');
    }
}
