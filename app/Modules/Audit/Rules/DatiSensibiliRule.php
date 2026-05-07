<?php

declare(strict_types=1);

namespace App\Modules\Audit\Rules;

/**
 * Mascheratura di campi sensibili in payload before/after.
 *
 * Casi tipici:
 *  - password / password_confirmation / remember_token
 *  - api_token / Bearer ... / Anthropic API key
 *  - carta credito / IBAN / codice fiscale
 *
 * Algoritmo:
 *  - chiavi blacklist => valore sostituito con '***MASKED***'
 *  - valori che matchano regex (Bearer/sk-/Token) => sostituiti
 *  - applicato ricorsivamente (array nested)
 *
 * NB: la regola è *stateless*: nessun side-effect, sicura in serie sui job.
 */
final class DatiSensibiliRule
{
    /** @var list<string> chiavi (case-insensitive) sempre mascherate */
    private const CHIAVI_SENSIBILI = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'remember_token',
        'api_token',
        'access_token',
        'refresh_token',
        'token',
        'secret',
        'client_secret',
        'private_key',
        'authorization',
        'cookie',
        'csrf_token',
        '_token',
        'codice_fiscale',
        'iban',
        'numero_carta',
        'cvv',
    ];

    /** Pattern di valori ad alto rischio (anche in chiavi non blacklist) */
    private const PATTERN_VALORI = [
        '/Bearer\s+[A-Za-z0-9._\-]+/i',
        '/sk-(?:ant-)?[A-Za-z0-9_\-]{20,}/',           // Anthropic / OpenAI keys
        '/[A-Za-z0-9]{32,}/',                            // long opaque tokens (best-effort)
    ];

    private const PLACEHOLDER = '***MASKED***';

    /**
     * Maschera un payload (array nested ammesso). Restituisce nuova array
     * (input non modificato).
     *
     * @param array<string,mixed>|null $payload
     * @return array<string,mixed>|null
     */
    public static function mask(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }
        return self::walk($payload);
    }

    /**
     * @param array<mixed,mixed> $arr
     * @return array<mixed,mixed>
     */
    private static function walk(array $arr): array
    {
        $out = [];
        foreach ($arr as $k => $v) {
            if (is_string($k) && self::eChiaveSensibile($k)) {
                $out[$k] = self::PLACEHOLDER;
                continue;
            }

            if (is_array($v)) {
                $out[$k] = self::walk($v);
            } elseif (is_string($v)) {
                $out[$k] = self::scrubValore($v);
            } else {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    private static function eChiaveSensibile(string $key): bool
    {
        $low = strtolower($key);
        foreach (self::CHIAVI_SENSIBILI as $bad) {
            if ($low === $bad || str_contains($low, $bad)) {
                return true;
            }
        }
        return false;
    }

    private static function scrubValore(string $v): string
    {
        // pattern aggressive: solo se la stringa è "sospetta" (no testo normale)
        // — euristica: lunghezza > 20 e poche spazi (token-shaped).
        if (strlen($v) > 20 && substr_count($v, ' ') <= 1) {
            foreach (self::PATTERN_VALORI as $pat) {
                if (preg_match($pat, $v) === 1) {
                    return self::PLACEHOLDER;
                }
            }
        }
        return $v;
    }
}
