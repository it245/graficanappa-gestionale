<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke tests login operatore.
 *
 * Copre:
 *  - GET /operatore/login → 200 (form)
 *  - POST con codice operatore non esistente → redirect back con error
 *
 * Codici operatore sono alfanumerici (no input numeric).
 * Vedi OperatoreLoginRequest per le regole di validazione.
 */
class OperatoreLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_operatore_login_form_renders(): void
    {
        $response = $this->get('/operatore/login');

        $response->assertStatus(200);
    }

    public function test_operatore_login_with_invalid_code_redirects_back_with_error(): void
    {
        // Codice valido come formato (alfanumerico) ma inesistente in DB →
        // controller deve fare back()->withErrors(['codice_operatore' => ...]).
        $response = $this->from('/operatore/login')->post('/operatore/login', [
            'codice_operatore' => 'NONESISTE99',
        ]);

        $response->assertRedirect('/operatore/login');
        $response->assertSessionHasErrors(['codice_operatore']);
    }

    public function test_operatore_login_with_empty_code_fails_validation(): void
    {
        $response = $this->from('/operatore/login')->post('/operatore/login', [
            'codice_operatore' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['codice_operatore']);
    }
}
