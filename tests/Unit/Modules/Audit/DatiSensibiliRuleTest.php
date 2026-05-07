<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Audit;

use App\Modules\Audit\Rules\DatiSensibiliRule;
use PHPUnit\Framework\TestCase;

/**
 * Verifica mascheratura dati sensibili in payload audit.
 */
final class DatiSensibiliRuleTest extends TestCase
{
    public function test_password_chiave_blacklist_viene_mascherata(): void
    {
        $out = DatiSensibiliRule::mask([
            'name' => 'Mario',
            'password' => 'segreto123!',
        ]);

        $this->assertSame('Mario', $out['name']);
        $this->assertSame('***MASKED***', $out['password']);
    }

    public function test_password_confirmation_e_remember_token_mascherati(): void
    {
        $out = DatiSensibiliRule::mask([
            'password_confirmation' => 'x',
            'remember_token' => 'abcdef',
        ]);

        $this->assertSame('***MASKED***', $out['password_confirmation']);
        $this->assertSame('***MASKED***', $out['remember_token']);
    }

    public function test_bearer_token_in_valore_mascherato(): void
    {
        $out = DatiSensibiliRule::mask([
            'authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.payload.sig',
        ]);

        $this->assertSame('***MASKED***', $out['authorization']);
    }

    public function test_anthropic_api_key_pattern_mascherato(): void
    {
        $out = DatiSensibiliRule::mask([
            'note' => 'sk-ant-api03-AAAAAAAAAAAAAAAAAAAAAAAAA',
        ]);

        $this->assertSame('***MASKED***', $out['note']);
    }

    public function test_payload_innocuo_resta_invariato(): void
    {
        $in = ['stato' => 2, 'descrizione' => 'fase avviata'];
        $out = DatiSensibiliRule::mask($in);

        $this->assertSame($in, $out);
    }

    public function test_array_nested_mascherato_ricorsivamente(): void
    {
        $out = DatiSensibiliRule::mask([
            'utente' => ['nome' => 'Mario', 'password' => 'x'],
            'meta' => ['ip' => '10.0.0.1'],
        ]);

        $this->assertSame('Mario', $out['utente']['nome']);
        $this->assertSame('***MASKED***', $out['utente']['password']);
        $this->assertSame('10.0.0.1', $out['meta']['ip']);
    }

    public function test_input_null_resta_null(): void
    {
        $this->assertNull(DatiSensibiliRule::mask(null));
    }

    public function test_chiave_contiene_password_anche_se_non_esatta(): void
    {
        $out = DatiSensibiliRule::mask([
            'old_password' => 'a',
            'newPassword' => 'b',
        ]);

        $this->assertSame('***MASKED***', $out['old_password']);
        $this->assertSame('***MASKED***', $out['newPassword']);
    }
}
