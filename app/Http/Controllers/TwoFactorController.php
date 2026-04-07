<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\DB;

class TwoFactorController extends Controller
{
    /**
     * Pagina setup 2FA (mostra QR code).
     */
    public function setup(Request $request)
    {
        $opId = session('operatore_id');
        if (!$opId) return redirect('/admin/login');

        $op = DB::table('operatori')->where('id', $opId)->first();

        // Genera secret se non esiste
        $secret = $op->two_factor_secret;
        if (!$secret) {
            $secret = TwoFactorService::generateSecret();
            DB::table('operatori')->where('id', $opId)->update([
                'two_factor_secret' => encrypt($secret),
            ]);
        } else {
            $secret = decrypt($secret);
        }

        $qrUrl = TwoFactorService::getQrUrl($op->nome . '@graficanappa', $secret);

        return view('admin.two_factor_setup', compact('qrUrl', 'secret', 'op'));
    }

    /**
     * Conferma setup 2FA (verifica primo codice).
     */
    public function confirm(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $opId = session('operatore_id');
        $op = DB::table('operatori')->where('id', $opId)->first();
        $secret = decrypt($op->two_factor_secret);

        if (!TwoFactorService::verifyCode($secret, $request->code)) {
            return back()->withErrors(['code' => 'Codice non valido. Riprova.']);
        }

        // Genera recovery codes
        $recoveryCodes = TwoFactorService::generateRecoveryCodes();

        DB::table('operatori')->where('id', $opId)->update([
            'two_factor_enabled' => true,
            'two_factor_recovery_codes' => json_encode($recoveryCodes),
        ]);

        return view('admin.two_factor_recovery', compact('recoveryCodes'));
    }

    /**
     * Disabilita 2FA.
     */
    public function disable(Request $request)
    {
        $opId = session('operatore_id');
        DB::table('operatori')->where('id', $opId)->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);

        // Rimuovi dispositivi fidati
        DB::table('trusted_devices')->where('operatore_id', $opId)->delete();

        return redirect('/admin/dashboard')->with('success', '2FA disabilitato.');
    }

    /**
     * Pagina verifica 2FA (dopo login admin).
     */
    public function challenge(Request $request)
    {
        if (!session('2fa_pending')) return redirect('/admin/dashboard');
        return view('admin.two_factor_challenge');
    }

    /**
     * Verifica codice 2FA dopo login.
     */
    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $opId = session('operatore_id');
        $op = DB::table('operatori')->where('id', $opId)->first();
        $secret = decrypt($op->two_factor_secret);
        $code = trim($request->code);

        // Prova come codice TOTP
        $valid = TwoFactorService::verifyCode($secret, $code);

        // Se fallisce, prova come recovery code
        if (!$valid) {
            $valid = TwoFactorService::verifyRecoveryCode($opId, $code);
        }

        if (!$valid) {
            return back()->withErrors(['code' => 'Codice non valido.']);
        }

        // 2FA superato — registra dispositivo fidato
        session()->forget('2fa_pending');
        $token = TwoFactorService::trustDevice($request, $opId);

        return redirect('/admin/dashboard')
            ->cookie(TwoFactorService::COOKIE_NAME, $token, TwoFactorService::COOKIE_DAYS * 24 * 60);
    }

    /**
     * Lista dispositivi fidati.
     */
    public function devices(Request $request)
    {
        $opId = session('operatore_id');
        $devices = DB::table('trusted_devices')
            ->where('operatore_id', $opId)
            ->orderByDesc('last_used_at')
            ->get();

        return view('admin.two_factor_devices', compact('devices'));
    }

    /**
     * Revoca un dispositivo fidato.
     */
    public function revokeDevice(Request $request, $id)
    {
        $opId = session('operatore_id');
        DB::table('trusted_devices')
            ->where('id', $id)
            ->where('operatore_id', $opId)
            ->delete();

        return back()->with('success', 'Dispositivo revocato.');
    }
}
