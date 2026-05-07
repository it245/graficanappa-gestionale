<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Macchine;

use App\Modules\Macchine\Contracts\MacchinaInterface;
use App\Modules\Macchine\MacchinaRegistry;
use App\Modules\Macchine\Models\MacchinaConfig;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit test del MacchinaRegistry.
 *
 * Verifica le 7 macchine note di Grafica Nappa (Mossa 37):
 * XL106, JOH, BOBST, PIEGA(incolla), STEL, INDIGO, ZUND.
 */
final class MacchinaRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset cache per indipendenza tra test.
        MacchinaRegistry::flush();
    }

    /**
     * @dataProvider provideMacchineNote
     */
    public function test_macchina_nota_e_registrata_e_implementa_contract(string $id): void
    {
        $this->assertTrue(
            MacchinaRegistry::exists($id),
            "La macchina '{$id}' dovrebbe essere registrata nel registry."
        );

        $regola = MacchinaRegistry::find($id);
        $this->assertInstanceOf(MacchinaInterface::class, $regola);
        $this->assertSame($id, $regola->getId());
        $this->assertNotSame('', $regola->getNome());
        $this->assertGreaterThan(0, $regola->getCapacitaOraria());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function provideMacchineNote(): array
    {
        return [
            'XL106 stampa offset' => ['XL106'],
            'JOH stampa caldo'    => ['JOH'],
            'BOBST fustella'      => ['BOBST'],
            'PIEGA incolla'       => ['PIEGA'],
            'STEL fust cilindr.'  => ['STEL'],
            'INDIGO digitale'     => ['INDIGO'],
            'ZUND finitura dig.'  => ['ZUND'],
        ];
    }

    public function test_xl106_lavora_24h_lun_ven(): void
    {
        $cfg = MacchinaRegistry::find('XL106')->toConfig();

        $this->assertInstanceOf(MacchinaConfig::class, $cfg);
        $this->assertSame(24.0, $cfg->oreFeriali());
        $this->assertFalse($cfg->lavoraSabato);
        // 24 * 5 giorni = 120 ore settimanali
        $this->assertSame(120.0, $cfg->oreSettimanali());
    }

    public function test_joh_lavora_anche_sabato_mattina(): void
    {
        $regola = MacchinaRegistry::find('JOH');
        $cfg    = $regola->toConfig();

        $this->assertTrue($cfg->lavoraSabato, 'JOH deve lavorare il sabato.');
        $this->assertSame(7.0, $cfg->oreSabato);
        // 16h feriali * 5 + 7 sabato = 87
        $this->assertSame(87.0, $cfg->oreSettimanali());
    }

    public function test_bobst_richiede_cambio_config_un_ora(): void
    {
        $regola = MacchinaRegistry::find('BOBST');

        $this->assertTrue($regola->richiedeCambioConfig());
        $this->assertSame(1.0, $regola->oreCambioConfig());
    }

    public function test_piegaincolla_richiede_cambio_config_un_ora(): void
    {
        $regola = MacchinaRegistry::find('PIEGA');

        $this->assertTrue($regola->richiedeCambioConfig());
        $this->assertSame(1.0, $regola->oreCambioConfig());
        $this->assertContains('PI01', $regola->toConfig()->meta['configurazioni'] ?? []);
    }

    public function test_macchina_inesistente_solleva_eccezione(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MacchinaRegistry::find('NON_ESISTENTE');
    }

    public function test_find_e_case_insensitive(): void
    {
        $a = MacchinaRegistry::find('xl106');
        $b = MacchinaRegistry::find('XL106');

        $this->assertSame($a, $b, 'find() dovrebbe normalizzare l id in uppercase.');
    }
}
