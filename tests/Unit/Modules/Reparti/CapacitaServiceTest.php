<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Reparti;

use App\Modules\Reparti\Enums\CodiceReparto;
use App\Modules\Reparti\Services\CapacitaService;
use App\Modules\Reparti\Services\TurniService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Unit test di CapacitaService.
 *
 * Verifica le 3 dimensioni dei turni Grafica Nappa:
 *  1. Feriale (lun-ven): turni completi
 *  2. Sabato: solo mattina (8h)
 *  3. Festivo / domenica: 0 ore
 */
final class CapacitaServiceTest extends TestCase
{
    private CapacitaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CapacitaService(new TurniService());
    }

    public function test_offset_feriale_24h(): void
    {
        // Mercoledì 13 maggio 2026 (feriale qualunque)
        $giorno = CarbonImmutable::create(2026, 5, 13);
        $ore = $this->service->oreDisponibiliGiorno(CodiceReparto::STAMPA_OFFSET, $giorno);
        $this->assertSame(24, $ore, 'XL106 feriale deve avere 24h (3 turni)');
    }

    public function test_caldo_feriale_16h(): void
    {
        $giorno = CarbonImmutable::create(2026, 5, 13);
        $ore = $this->service->oreDisponibiliGiorno(CodiceReparto::STAMPA_A_CALDO, $giorno);
        $this->assertSame(16, $ore, 'JOH feriale deve avere 16h (2 turni)');
    }

    public function test_finitura_feriale_16h(): void
    {
        $giorno = CarbonImmutable::create(2026, 5, 13);
        $ore = $this->service->oreDisponibiliGiorno(CodiceReparto::PIEGAINCOLLA, $giorno);
        $this->assertSame(16, $ore, 'Piegaincolla feriale deve avere 16h (mattina+pomeriggio)');
    }

    public function test_sabato_solo_mattina_8h(): void
    {
        // Sabato 16 maggio 2026
        $giorno = CarbonImmutable::create(2026, 5, 16);
        $ore = $this->service->oreDisponibiliGiorno(CodiceReparto::PIEGAINCOLLA, $giorno);
        $this->assertSame(8, $ore, 'Sabato solo turno mattina (8h)');
    }

    public function test_domenica_zero_ore(): void
    {
        // Domenica 17 maggio 2026
        $giorno = CarbonImmutable::create(2026, 5, 17);
        $ore = $this->service->oreDisponibiliGiorno(CodiceReparto::STAMPA_OFFSET, $giorno);
        $this->assertSame(0, $ore, 'Domenica zero ore anche per offset');
    }

    public function test_festivo_25_aprile_zero_ore(): void
    {
        $giorno = CarbonImmutable::create(2026, 4, 25); // Festa Liberazione (sabato in 2026, ma festivo)
        $ore = $this->service->oreDisponibiliGiorno(CodiceReparto::STAMPA_OFFSET, $giorno);
        $this->assertSame(0, $ore, 'Festa Liberazione 25 aprile: zero ore');
    }

    public function test_natale_zero_ore(): void
    {
        $giorno = CarbonImmutable::create(2026, 12, 25);
        $ore = $this->service->oreDisponibiliGiorno(CodiceReparto::DIGITALE, $giorno);
        $this->assertSame(0, $ore, 'Natale: zero ore');
    }

    public function test_ore_settimana_offset_feriali(): void
    {
        // settimana lun 11 maggio 2026 → dom 17 maggio 2026
        $lunedi = CarbonImmutable::create(2026, 5, 11);
        $ore = $this->service->oreDisponibiliSettimana(CodiceReparto::STAMPA_OFFSET, $lunedi);
        // 5 giorni feriali × 24h + sabato 8h + domenica 0h = 128
        $this->assertSame(128, $ore);
    }
}
