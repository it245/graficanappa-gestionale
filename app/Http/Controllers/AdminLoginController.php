<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Operatore;
use Illuminate\Support\Facades\Hash;

class AdminLoginController extends Controller
{
    public function form()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'codice_operatore' => 'required|string',
            'password' => 'required|string',
        ]);

        $operatore = Operatore::where('codice_operatore', $request->codice_operatore)
            ->where('ruolo', 'admin')
            ->where('attivo', 1)
            ->first();

        if (!$operatore || !Hash::check($request->password, $operatore->password)) {
            return back()->withErrors(['login' => 'Credenziali non valide.']);
        }

        $request->session()->put([
            'operatore_id' => $operatore->id,
            'operatore_nome' => $operatore->nome,
            'operatore_ruolo' => $operatore->ruolo,
        ]);

        return redirect('/admin/dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect('/admin/login');
    }
}
