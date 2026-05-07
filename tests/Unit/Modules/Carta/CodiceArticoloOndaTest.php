<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Carta;

use App\Modules\Carta\ValueObjects\CodiceArticoloOnda;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Cattura il comportamento del parser cod_art Onda
 * (`02W.MARCA.TIPO.GRAMMATURA.SEQ`).
 *
 * Sono codici reali presenti in produzione (anagrafica articoli MES).
 */
class CodiceArticoloOndaTest extends TestCase
{
    public function test_parse_codice_alaska_gc1_300_003(): void
    {
        $vo = CodiceArticoloOnda::daStringa('02W.ALASKA.GC1.300.003');

        $this->assertSame('ALASKA', $vo->marca);
        $this->assertSame('GC1', $vo->tipo);
        $this->assertSame(300, $vo->grammatura);
        $this->assertSame(3, $vo->sequenza);
        $this->assertSame('02W.ALASKA.GC1.300.003', $vo->formato());
        $this->assertTrue($vo->eValido());
    }

    public function test_parse_normalizza_in_maiuscolo(): void
    {
        $vo = CodiceArticoloOnda::daStringa('  02w.alaska.gc1.300.003  ');

        $this->assertSame('ALASKA', $vo->marca);
        $this->assertSame('GC1', $vo->tipo);
        $this->assertSame('02W.ALASKA.GC1.300.003', (string) $vo);
    }

    /**
     * @dataProvider codiciValidi
     */
    public function test_parse_codici_validi(string $cod, string $marca, string $tipo, int $grammatura, int $sequenza): void
    {
        $vo = CodiceArticoloOnda::daStringa($cod);

        $this->assertSame($marca, $vo->marca);
        $this->assertSame($tipo, $vo->tipo);
        $this->assertSame($grammatura, $vo->grammatura);
        $this->assertSame($sequenza, $vo->sequenza);
    }

    public static function codiciValidi(): array
    {
        return [
            'cartoncino vergine 300gr' => ['02W.ALASKA.GC1.300.003', 'ALASKA', 'GC1', 300, 3],
            'patinata 100gr seq 1' => ['02W.FEDRI.PAT.100.001', 'FEDRI', 'PAT', 100, 1],
            'cartoncino 80gr seq 999' => ['02W.MARCA1.GC2.80.999', 'MARCA1', 'GC2', 80, 999],
            'grammatura 4 cifre' => ['02W.ALPHA.GC1.1000.5', 'ALPHA', 'GC1', 1000, 5],
        ];
    }

    /**
     * @dataProvider codiciInvalidi
     */
    public function test_prova_da_stringa_ritorna_null_per_codici_invalidi(string $cod): void
    {
        $this->assertNull(CodiceArticoloOnda::provaDaStringa($cod));
    }

    public static function codiciInvalidi(): array
    {
        return [
            'prefisso sbagliato' => ['03W.ALASKA.GC1.300.003'],
            'mancante segmento' => ['02W.ALASKA.GC1.300'],
            'segmento extra' => ['02W.ALASKA.GC1.300.003.X'],
            'grammatura non numerica' => ['02W.ALASKA.GC1.ABC.003'],
            'codice legacy non Onda' => ['SEMILAV001'],
            'codice magazzino interno' => ['ALASKAGC1.70X100.300'],
            'stringa vuota' => [''],
            'caratteri speciali' => ['02W.ALA-SKA.GC1.300.003'],
        ];
    }

    public function test_da_stringa_lancia_eccezione_su_codice_invalido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Codice articolo Onda non valido");

        CodiceArticoloOnda::daStringa('SEMILAV001');
    }

    public function test_e_valido_false_per_grammatura_fuori_range(): void
    {
        $vo = CodiceArticoloOnda::daStringa('02W.ALPHA.GC1.1000.5');

        // 1000gr supera il limite plausibile 600 → eValido() false.
        $this->assertFalse($vo->eValido());
    }

    public function test_to_string_ritorna_stringa_originale_normalizzata(): void
    {
        $vo = CodiceArticoloOnda::daStringa('02w.alaska.gc1.300.003');

        $this->assertSame('02W.ALASKA.GC1.300.003', (string) $vo);
    }
}
