<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Reportistica;

use App\Modules\Reportistica\Cache\ReportCache;
use App\Modules\Reportistica\Enums\TipoKpi;
use App\Modules\Reportistica\Services\KpiService;
use App\Modules\Reportistica\ValueObjects\KpiCard;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Unit test KpiService — verifica wiring cache + mapping VO.
 *
 * Mocka direttamente la cache (Cache::shouldReceive) per evitare di
 * dipendere dal DB MySQL .60 nei test unit.
 */
final class KpiServiceTest extends TestCase
{
    public function test_tutti_legge_payload_dalla_cache_e_mappa_a_kpi_card(): void
    {
        $payloadFinto = [
            ['tipo' => 'commesse_attive', 'label' => 'Commesse attive', 'valore' => 42, 'delta' => null,  'trend' => 'flat', 'unita' => ''],
            ['tipo' => 'fasi_oggi',       'label' => 'Fasi terminate oggi', 'valore' => 17, 'delta' => null, 'trend' => 'flat', 'unita' => ''],
            ['tipo' => 'ore_settimana',   'label' => 'Ore lavorate (7gg)', 'valore' => 380.5, 'delta' => 4.2, 'trend' => 'up', 'unita' => 'h'],
            ['tipo' => 'efficienza',      'label' => 'Efficienza', 'valore' => 95.0, 'delta' => null, 'trend' => 'flat', 'unita' => '%'],
            ['tipo' => 'puntualita',      'label' => 'Tasso puntualità', 'valore' => 88.0, 'delta' => null, 'trend' => 'flat', 'unita' => '%'],
            ['tipo' => 'scarto_prinect',  'label' => 'Scarto stampa offset', 'valore' => 2.1, 'delta' => null, 'trend' => 'flat', 'unita' => '%'],
        ];

        Cache::shouldReceive('remember')
            ->once()
            ->with(ReportCache::KEY_KPI, ReportCache::TTL_KPI, \Mockery::type('Closure'))
            ->andReturn($payloadFinto);

        $service = new KpiService();
        $cards = $service->tutti();

        $this->assertCount(6, $cards);
        $this->assertContainsOnlyInstancesOf(KpiCard::class, $cards);

        $primo = $cards->first();
        $this->assertSame(TipoKpi::COMMESSE_ATTIVE, $primo->tipo);
        $this->assertSame('Commesse attive', $primo->label);
        $this->assertSame(42, $primo->valore);

        // KPI ore_settimana ha delta positivo → trend "up"
        $oreSett = $cards->firstWhere('tipo', TipoKpi::ORE_SETTIMANA);
        $this->assertNotNull($oreSett);
        $this->assertSame(380.5, $oreSett->valore);
        $this->assertSame('up', $oreSett->trend);
        $this->assertSame('h', $oreSett->unita);
    }

    public function test_unico_filtra_per_tipo(): void
    {
        $payloadFinto = [
            ['tipo' => 'commesse_attive', 'label' => 'Commesse attive', 'valore' => 12, 'delta' => null, 'trend' => 'flat', 'unita' => ''],
            ['tipo' => 'fasi_oggi',       'label' => 'Fasi terminate oggi', 'valore' => 5,  'delta' => null, 'trend' => 'flat', 'unita' => ''],
            ['tipo' => 'ore_settimana',   'label' => 'Ore lavorate (7gg)', 'valore' => 0, 'delta' => null, 'trend' => 'flat', 'unita' => 'h'],
            ['tipo' => 'efficienza',      'label' => 'Efficienza', 'valore' => 0, 'delta' => null, 'trend' => 'flat', 'unita' => '%'],
            ['tipo' => 'puntualita',      'label' => 'Tasso puntualità', 'valore' => 0, 'delta' => null, 'trend' => 'flat', 'unita' => '%'],
            ['tipo' => 'scarto_prinect',  'label' => 'Scarto stampa offset', 'valore' => 0, 'delta' => null, 'trend' => 'flat', 'unita' => '%'],
        ];

        Cache::shouldReceive('remember')->andReturn($payloadFinto);

        $service = new KpiService();
        $card = $service->unico(TipoKpi::FASI_OGGI);

        $this->assertNotNull($card);
        $this->assertSame(TipoKpi::FASI_OGGI, $card->tipo);
        $this->assertSame(5, $card->valore);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
