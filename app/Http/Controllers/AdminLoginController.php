<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Operatore;
use Illuminate\Support\Facades\Hash;
use App\Services\AuditService;
use App\Services\TwoFactorService;

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

        AuditService::login($operatore->id, $operatore->nome . ' ' . ($operatore->cognome ?? ''));

        // 2FA: se abilitato e dispositivo non fidato → challenge
        if ($operatore->two_factor_enabled) {
            if (!TwoFactorService::isDeviceTrusted($request, $operatore->id)) {
                $request->session()->put('2fa_pending', true);
                return redirect('/admin/2fa/challenge');
            }
        }

        return redirect('/admin/dashboard');
    }

    public function logout(Request $request)
    {
        AuditService::logout();
        $request->session()->flush();
        return redirect('/admin/login');
    }
}
