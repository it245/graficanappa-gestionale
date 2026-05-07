<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Clickjacking protection
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // MIME sniffing protection
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions policy: blocca feature browser non necessarie
        // camera=() perché QR scan html5-qrcode usa getUserMedia solo su device dedicati;
        // se servirà su pagine specifiche, abilitare per quelle route via middleware dedicato
        $response->headers->set('Permissions-Policy',
            'camera=(), microphone=(), geolocation=()'
        );

        // Cross-domain policies
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // HSTS: forza HTTPS per 1 anno solo su connessioni effettivamente sicure
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Content Security Policy in modalità Report-Only (logga violazioni senza bloccare)
        // Whitelist CDN effettivamente usati: jsdelivr (Bootstrap/Choices/Chart/html5-qrcode/Pusher),
        // cdnjs (Echo), Google Fonts. 'unsafe-inline'+'unsafe-eval' richiesti da onclick legacy + Echo.
        // TODO: dopo 1 settimana monitor report, switch a CSP enforcing (Content-Security-Policy)
        if (!config('app.debug')) {
            $csp = "default-src 'self'; "
                . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
                . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; "
                . "font-src 'self' https://fonts.gstatic.com data:; "
                . "img-src 'self' data: blob: https:; "
                . "connect-src 'self' wss: ws:; "
                . "frame-ancestors 'self'; "
                . "object-src 'none'; "
                . "base-uri 'self'; "
                . "form-action 'self';";
            $response->headers->set('Content-Security-Policy-Report-Only', $csp);
        }

        return $response;
    }
}
