<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Comprime response HTML con gzip se browser supporta.
 * Riduce ~70% bytes wire (HTML 800KB -> 250KB) -> -1s LCP su connessioni medie.
 * Skip per response gia binarie/compresse, streamed o piccole.
 */
class GzipResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip se browser non supporta gzip
        $accept = $request->header('Accept-Encoding', '');
        if (!str_contains($accept, 'gzip')) return $response;

        // Skip se gia codificato o streamed
        if ($response->headers->get('Content-Encoding')) return $response;
        if ($response instanceof \Symfony\Component\HttpFoundation\StreamedResponse) return $response;
        if ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) return $response;

        $content = $response->getContent();
        if ($content === false || strlen($content) < 1024) return $response;

        // Skip se non testuale
        $contentType = strtolower($response->headers->get('Content-Type', 'text/html'));
        if (!preg_match('#^(text/|application/(json|javascript|xml|x-www-form-urlencoded))#', $contentType)) {
            return $response;
        }

        $compressed = gzencode($content, 6);
        if ($compressed === false) return $response;

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($compressed));
        $response->headers->set('Vary', 'Accept-Encoding');

        return $response;
    }
}
