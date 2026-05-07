<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke tests routing/middleware login admin.
 *
 * Copre:
 *  - GET /admin/login → 200 (form rendered)
 *  - POST /admin/login con credenziali invalide → redirect back con error generico
 *  - POST /admin/login senza campi → redirect back con errori di validazione (FormRequest)
 *
 * NB: AdminLoginRequest su rotta web fa redirect con errors (302), non 422.
 *     Solo richieste AJAX/expectsJson ricevono 422.
 */
class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_form_renders(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    }

    public function test_admin_login_with_invalid_credentials_redirects_back_with_error(): void
    {
        $response = $this->from('/admin/login')->post('/admin/login', [
            'codice_operatore' => 'NONESISTE',
            'password'         => 'wrongpassword',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors(['login']);
    }

    public function test_admin_login_with_empty_fields_returns_validation_errors(): void
    {
        // Web request: validation failure → 302 redirect back con errors in sessione.
        // 422 lo otterremmo solo con header Accept: application/json.
        $response = $this->from('/admin/login')->post('/admin/login', [
            'codice_operatore' => '',
            'password'         => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['codice_operatore', 'password']);
    }

    public function test_admin_login_with_empty_fields_returns_422_when_json(): void
    {
        // Variante JSON: il middleware FormRequest restituisce 422 invece di redirect.
        $response = $this->postJson('/admin/login', [
            'codice_operatore' => '',
            'password'         => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['codice_operatore', 'password']);
    }
}
