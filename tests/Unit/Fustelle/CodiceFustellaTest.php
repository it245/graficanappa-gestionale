<?php

declare(strict_types=1);

namespace Tests\Unit\Fustelle;

use App\Modules\Fustelle\ValueObjects\CodiceFustella;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Parsing del codice fustella canonico F-NNNNN-X.
 */
class CodiceFustellaTest extends TestCase
{
    /**
     * @dataProvider provideValidi
     */
    public function test_parse_codici_validi(string $input, string $atteso, int $numero, string $rev): void
    {
        $vo = CodiceFustella::daStringa($input);

        $this->assertSame($atteso, (string) $vo);
        $this->assertSame($numero, $vo->numero());
        $this->assertSame($rev, $vo->revisione());
    }

    public static function provideValidi(): array
    {
        return [
            'standard'           => ['F-12345-A', 'F-12345-A', 12345, 'A'],
            'minimo'             => ['F-00001-Z', 'F-00001-Z', 1, 'Z'],
            'lowercase trim'     => ['  f-00042-b  ', 'F-00042-B', 42, 'B'],
            'massimo cifre'      => ['F-99999-Q', 'F-99999-Q', 99999, 'Q'],
        ];
    }

    /**
     * @dataProvider provideInvalidi
     */
    public function test_parse_codici_invalidi_lancia_eccezione(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        CodiceFustella::daStringa($input);
    }

    public static function provideInvalidi(): array
    {
        return [
            'vuoto'              => [''],
            'senza prefisso'     => ['12345-A'],
            'cifre non bastanti' => ['F-1234-A'],
            'cifre eccessive'    => ['F-123456-A'],
            'rev minuscola fail' => ['F-12345-1'], // numero al posto di lettera
            'separatore sbagliato' => ['F_12345_A'],
            'legacy FS'          => ['FS0291'],
            'legacy KS'          => ['KS1234'],
            'rev multipla'       => ['F-12345-AB'],
        ];
    }

    public function test_prova_da_stringa_ritorna_null_su_invalido(): void
    {
        $this->assertNull(CodiceFustella::provaDaStringa('NON-VALIDO'));
        $this->assertNull(CodiceFustella::provaDaStringa(''));
    }

    public function test_da_legacy_riconosce_FS_e_KS(): void
    {
        $this->assertSame('FS0291', CodiceFustella::daLegacy('fs0291'));
        $this->assertSame('KS1234', CodiceFustella::daLegacy('  KS1234  '));
        $this->assertNull(CodiceFustella::daLegacy('F-12345-A'));
        $this->assertNull(CodiceFustella::daLegacy('XX0001'));
    }
}
