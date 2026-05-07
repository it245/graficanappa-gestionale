<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica che il middleware SecurityHeaders sia attivo globalmente
 * e applichi gli header anti-clickjacking / MIME sniffing su ogni risposta.
 *
 * Vedi app/Http/Middleware/SecurityHeaders.php (registrato in bootstrap/app.php).
 */
class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_security_headers(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);

        // Anti MIME sniffing
        $response->assertHeader('X-Content-Type-Options', 'nosniff');

        // Anti clickjacking
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');

        // Referrer policy
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Cross-domain policies bloccate
        $response->assertHeader('X-Permitted-Cross-Domain-Policies', 'none');
    }

    public function test_login_page_also_has_security_headers(): void
    {
        // Verifica che gli header siano applicati anche a route applicative,
        // non solo a /up (regression guard).
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
