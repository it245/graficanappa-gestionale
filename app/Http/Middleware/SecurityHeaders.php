<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Impedisce che la pagina venga caricata in un iframe (clickjacking)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Il browser non deve "indovinare" il tipo MIME — usa quello dichiarato
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Politica sui referrer: manda solo l'origine, non il path completo
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Blocca funzionalità browser non necessarie (camera, microfono, geolocalizzazione)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}
