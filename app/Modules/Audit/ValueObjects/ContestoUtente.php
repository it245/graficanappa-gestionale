<?php

declare(strict_types=1);

namespace App\Modules\Audit\ValueObjects;

/**
 * Snapshot immutable dell'utente al momento dell'evento auditato.
 *
 * NON è un model: è un VO che congela id/nome/IP/UA al tempo del log.
 * Anche se l'utente viene poi cancellato (GDPR right-to-erasure),
 * lo snapshot pseudonimizzato resta valido per audit trail.
 */
final class ContestoUtente
{
    public function __construct(
        public readonly ?int $userId = null,
        public readonly ?string $userName = null,
        public readonly ?string $ip = null,
        public readonly ?string $userAgent = null,
        public readonly ?string $sessioneId = null,
    ) {}

    /**
     * Costruisce il VO leggendo da auth() / session() / request() correnti.
     * Sicuro fuori da contesto HTTP (es. da console/queue): tutti null.
     */
    public static function dalContestoCorrente(): self
    {
        // ID utente: prima sessione operatore (legacy), poi auth Eloquent (admin/owner)
        $userId   = null;
        $userName = null;

        try {
            if (function_exists('session') && session()->isStarted()) {
                $userId   = session('operatore_id');
                $userName = session('operatore_nome');
            }
        } catch (\Throwable) {
            // session() può lanciare in console
        }

        if ($userId === null) {
            try {
                if (function_exists('auth') && auth()->check()) {
                    $u = auth()->user();
                    $userId   = (int) ($u->id ?? 0) ?: null;
                    $userName = (string) ($u->name ?? $u->username ?? '') ?: null;
                }
            } catch (\Throwable) {
                // auth() può lanciare se guards non bootstrapped
            }
        }

        $ip        = null;
        $userAgent = null;
        $sessione  = null;

        try {
            if (function_exists('request') && request() !== null) {
                $ip        = request()->ip();
                $userAgent = substr((string) (request()->userAgent() ?? ''), 0, 500) ?: null;
            }
            if (function_exists('session') && session()->isStarted()) {
                $sessione = session()->getId();
            }
        } catch (\Throwable) {
            // ok in console
        }

        return new self(
            userId: $userId !== null ? (int) $userId : null,
            userName: $userName,
            ip: $ip,
            userAgent: $userAgent,
            sessioneId: $sessione,
        );
    }
}
