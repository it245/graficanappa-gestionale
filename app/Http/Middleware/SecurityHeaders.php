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
        $response->headers->set('Permissions-Policy',
            'camera=(self), microphone=(), geolocation=(), payment=(), usb=(), '
            . 'accelerometer=(), gyroscope=(), magnetometer=(), interest-cohort=()'
        );

        // Cross-domain policies
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // HSTS: forza HTTPS per 1 anno (attivare SOLO dopo aver verificato HTTPS stabile)
        if (config('app.force_https', false) && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Content Security Policy in modalità Report-Only (logga violazioni senza bloccare)
        // Permette inline-script/style perché MES usa molti onclick + style attribute legacy
        // TODO: migrare a CSP enforce dopo refactor inline scripts/styles
        if (!config('app.debug')) {
            $csp = "default-src 'self' https:; "
                . "script-src 'self' 'unsafe-inline' 'unsafe-eval' https: data:; "
                . "style-src 'self' 'unsafe-inline' https: data:; "
                . "img-src 'self' data: https: blob:; "
                . "font-src 'self' data: https://fonts.gstatic.com; "
                . "connect-src 'self' https: wss: ws:; "
                . "media-src 'self' data: blob:; "
                . "object-src 'none'; "
                . "base-uri 'self'; "
                . "form-action 'self'; "
                . "frame-ancestors 'self';";
            $response->headers->set('Content-Security-Policy-Report-Only', $csp);
        }

        return $response;
    }
}
