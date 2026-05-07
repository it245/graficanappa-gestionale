<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Presenze;

use App\Modules\Presenze\ValueObjects\BadgeOperatore;
use PHPUnit\Framework\TestCase;

/**
 * Validazione del VO BadgeOperatore.
 *
 * Convenzione NetTime Grafica Nappa:
 *  - matricola = 4-8 cifne
 *  - normalizzazione via ::da() → 6 cifre zero-padded
 */
class BadgeOperatoreTest extends TestCase
{
    public function test_accetta_matricola_6_cifre(): void
    {
        $b = new BadgeOperatore('000123');
        $this->assertSame('000123', $b->matricola);
    }

    public function test_da_normalizza_a_6_cifre(): void
    {
        $b = BadgeOperatore::da('123');
        $this->assertSame('000123', $b->matricola);
    }

    public function test_da_accetta_int(): void
    {
        $b = BadgeOperatore::da(45);
        $this->assertSame('000045', $b->matricola);
    }

    public function test_da_strippa_zeri_iniziali_eccessivi(): void
    {
        $b = BadgeOperatore::da('00000045');
        $this->assertSame('000045', $b->matricola);
    }

    public function test_rifiuta_matricola_con_lettere(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new BadgeOperatore('ABC123');
    }

    public function test_rifiuta_matricola_vuota(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new BadgeOperatore('');
    }

    public function test_rifiuta_matricola_oltre_8_cifre(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new BadgeOperatore('123456789');
    }

    public function test_equals(): void
    {
        $a = BadgeOperatore::da('123');
        $b = BadgeOperatore::da('000123');
        $c = BadgeOperatore::da('456');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_to_string(): void
    {
        $b = BadgeOperatore::da('456');
        $this->assertSame('000456', (string) $b);
    }
}
