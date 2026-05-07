<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test: l'health endpoint /up deve sempre rispondere 200.
 * Usato da load balancer / monitoring per verificare se l'app è up.
 *
 * /up è configurato in bootstrap/app.php (Laravel 11+ health endpoint).
 */
class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_200(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }
}
