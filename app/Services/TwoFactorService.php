<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class TwoFactorService
{
    const COOKIE_NAME = '2fa_trusted_device';
    const COOKIE_DAYS = 365 * 10; // 10 anni (praticamente per sempre, come Fortnite)

    /**
     * Verifica se il dispositivo corrente è fidato per questo utente.
     */
    public static function isDeviceTrusted(Request $request, int $operatoreId): bool
    {
        $token = $request->cookie(self::COOKIE_NAME);
        if (!$token) return false;

        $device = DB::table('trusted_devices')
            ->where('operatore_id', $operatoreId)
            ->where('device_token_hash', hash('sha256', $token))
            ->first();

        if ($device) {
            // Aggiorna last_used_at
            DB::table('trusted_devices')
                ->where('id', $device->id)
                ->update(['last_used_at' => now()]);
            return true;
        }

        return false;
    }

    /**
     * Registra il dispositivo corrente come fidato.
     * Restituisce il token da salvare nel cookie.
     */
    public static function trustDevice(Request $request, int $operatoreId): string
    {
        $token = Str::random(64);

        DB::table('trusted_devices')->insert([
            'operatore_id' => $operatoreId,
            'device_token_hash' => hash('sha256', $token),
            'device_name' => self::parseDeviceName($request->userAgent()),
            'ip_first_use' => $request->ip(),
            'last_used_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $token;
    }

    /**
     * Verifica il codice TOTP a 6 cifre.
     */
    public static function verifyCode(string $secret, string $code): bool
    {
        if (!class_exists(\PragmaRX\Google2FA\Google2FA::class)) {
            return false;
        }
        $g2fa = new \PragmaRX\Google2FA\Google2FA();
        return $g2fa->verifyKey($secret, $code);
    }

    /**
     * Genera un nuovo secret 2FA.
     */
    public static function generateSecret(): string
    {
        if (!class_exists(\PragmaRX\Google2FA\Google2FA::class)) {
            return '';
        }
        $g2fa = new \PragmaRX\Google2FA\Google2FA();
        return $g2fa->generateSecretKey();
    }

    /**
     * Genera l'URL per il QR code.
     */
    public static function getQrUrl(string $email, string $secret): string
    {
        if (!class_exists(\PragmaRX\Google2FA\Google2FA::class)) {
            return '';
        }
        $g2fa = new \PragmaRX\Google2FA\Google2FA();
        return $g2fa->getQRCodeUrl('GraficaNappa MES', $email, $secret);
    }

    /**
     * Genera 8 recovery codes monouso.
     */
    public static function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(Str::random(4) . '-' . Str::random(4));
        }
        return $codes;
    }

    /**
     * Verifica un recovery code e lo invalida se corretto.
     */
    public static function verifyRecoveryCode(int $operatoreId, string $code): bool
    {
        $op = DB::table('operatori')->where('id', $operatoreId)->first();
        if (!$op || !$op->two_factor_recovery_codes) return false;

        $codes = json_decode($op->two_factor_recovery_codes, true);
        $code = strtoupper(trim($code));

        $index = array_search($code, $codes);
        if ($index === false) return false;

        // Rimuovi il codice usato
        unset($codes[$index]);
        DB::table('operatori')
            ->where('id', $operatoreId)
            ->update(['two_factor_recovery_codes' => json_encode(array_values($codes))]);

        return true;
    }

    /**
     * L'utente ha il 2FA abilitato?
     */
    public static function isEnabled(int $operatoreId): bool
    {
        return (bool) DB::table('operatori')
            ->where('id', $operatoreId)
            ->value('two_factor_enabled');
    }

    /**
     * Estrai nome dispositivo dal User-Agent.
     */
    private static function parseDeviceName(?string $ua): string
    {
        if (!$ua) return 'Sconosciuto';
        if (str_contains($ua, 'iPhone')) return 'iPhone';
        if (str_contains($ua, 'iPad')) return 'iPad';
        if (str_contains($ua, 'Android')) return 'Android';
        if (str_contains($ua, 'Mac')) return 'Mac';
        if (str_contains($ua, 'Windows')) return 'Windows PC';
        if (str_contains($ua, 'Linux')) return 'Linux';
        return substr($ua, 0, 50);
    }
}
