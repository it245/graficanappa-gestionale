<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Spedizione\Services\DdtSyncService;
use Illuminate\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Facade;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Test unit del {@see DdtSyncService} (Strangler Fig di OndaSyncService::sincronizzaDDTVendita).
 *
 * Strategia: alias-mock degli static Eloquent (Ordine, DdtSpedizione) per evitare
 * setup completo Laravel/DB. Mock DatabaseManager per il SELECT su Onda + transaction.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DdtSyncServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Container minimale con un logger no-op così il Log facade non esplode.
        $container = new Container();
        $container->instance('log', new class extends NullLogger {
            public function info($message, array $context = []): void {}
            public function warning($message, array $context = []): void {}
        });
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Verifica che righe SQL aggregate per (commessa+cod_art) producano DDT
     * separati: due CodArt diversi sulla stessa commessa = due updateOrCreate
     * con qta distinte (no merge accidentale).
     */
    public function test_it_groups_by_commessa_and_articolo(): void
    {
        // Due righe Onda con stessa commessa ma CodArt diversi.
        $righe = [
            $this->riga(idDoc: 1001, commessa: 'C100', codArt: 'ART-A', qta: 500.0, numero: 'DDT-001'),
            $this->riga(idDoc: 1001, commessa: 'C100', codArt: 'ART-B', qta: 250.0, numero: 'DDT-001'),
        ];

        $db = $this->mockDbReturning($righe);

        // Alias mock: Ordine::where(...)->where(...)->first() ritorna stub Ordine
        // diverso per ogni cod_art.
        $ordineA = $this->ordineStub(id: 11);
        $ordineB = $this->ordineStub(id: 22);

        $ordineMock = Mockery::mock('alias:App\Models\Ordine');
        $queryA = Mockery::mock();
        $queryA->shouldReceive('where')->with('cod_art', 'ART-A')->andReturnSelf();
        $queryA->shouldReceive('first')->andReturn($ordineA);

        $queryB = Mockery::mock();
        $queryB->shouldReceive('where')->with('cod_art', 'ART-B')->andReturnSelf();
        $queryB->shouldReceive('first')->andReturn($ordineB);

        $ordineMock->shouldReceive('where')
            ->with('commessa', 'C100')
            ->andReturn($queryA, $queryB);

        // Alias mock DdtSpedizione: track delle chiamate updateOrCreate.
        $captured = [];
        $ddtMock = Mockery::mock('alias:App\Models\DdtSpedizione');
        $existsQuery = Mockery::mock();
        $existsQuery->shouldReceive('where')->andReturnSelf();
        $existsQuery->shouldReceive('exists')->andReturn(false);
        $ddtMock->shouldReceive('where')->andReturn($existsQuery);
        $ddtMock->shouldReceive('updateOrCreate')
            ->andReturnUsing(function ($keys, $values) use (&$captured) {
                $captured[] = ['keys' => $keys, 'values' => $values];
                return (object) array_merge($keys, $values);
            });

        // DdtPdfService alias: no-op per i DDT nuovi.
        Mockery::mock('alias:App\Services\DdtPdfService')
            ->shouldReceive('generaESalva')->andReturnNull();

        $service = new DdtSyncService($db);
        $result = $service->syncFromOnda(30);

        $this->assertSame(2, $result['inseriti']);
        $this->assertSame(0, $result['errori']);
        $this->assertCount(2, $captured, 'Due CodArt diversi devono generare due updateOrCreate distinti');
        $this->assertSame(500.0, $captured[0]['values']['qta']);
        $this->assertSame(250.0, $captured[1]['values']['qta']);
        // Conferma che il match per cod_art ha effettivamente differenziato gli ordini.
        $this->assertSame(11, $captured[0]['values']['ordine_id']);
        $this->assertSame(22, $captured[1]['values']['ordine_id']);
    }

    /**
     * Verifica che updateOrCreate venga invocato anche quando il DDT esiste
     * già (resync): la qta deve essere ri-passata per aggiornare il record.
     */
    public function test_it_updates_existing_ddt_qta_on_resync(): void
    {
        $righe = [
            $this->riga(idDoc: 2002, commessa: 'C200', codArt: 'ART-X', qta: 999.0, numero: 'DDT-999'),
        ];

        $db = $this->mockDbReturning($righe);

        $ordineMock = Mockery::mock('alias:App\Models\Ordine');
        $query = Mockery::mock();
        $query->shouldReceive('where')->andReturnSelf();
        $query->shouldReceive('first')->andReturn($this->ordineStub(id: 77));
        $ordineMock->shouldReceive('where')->andReturn($query);

        $captured = [];
        $ddtMock = Mockery::mock('alias:App\Models\DdtSpedizione');
        // Simula DDT già esistente: exists() → true (no PDF, contatore aggiornati).
        $existsQuery = Mockery::mock();
        $existsQuery->shouldReceive('where')->andReturnSelf();
        $existsQuery->shouldReceive('exists')->andReturn(true);
        $ddtMock->shouldReceive('where')->andReturn($existsQuery);
        $ddtMock->shouldReceive('updateOrCreate')
            ->andReturnUsing(function ($keys, $values) use (&$captured) {
                $captured[] = ['keys' => $keys, 'values' => $values];
                return (object) array_merge($keys, $values);
            });

        Mockery::mock('alias:App\Services\DdtPdfService')
            ->shouldReceive('generaESalva')->andReturnNull();

        $service = new DdtSyncService($db);
        $result = $service->syncFromOnda(30);

        $this->assertSame(0, $result['inseriti'], 'DDT preesistente: niente "inseriti"');
        $this->assertSame(1, $result['aggiornati'], 'DDT preesistente: deve incrementare "aggiornati"');
        $this->assertCount(1, $captured);
        $this->assertSame(999.0, $captured[0]['values']['qta'], 'updateOrCreate deve ri-passare la qta corrente');
        $this->assertSame(2002, $captured[0]['keys']['onda_id_doc']);
        $this->assertSame('C200', $captured[0]['keys']['commessa']);
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Costruisce un mock DatabaseManager che:
     *  - su connection('onda')->select(...) ritorna le righe fornite
     *  - su connection()->transaction($cb) esegue $cb senza wrapper reale
     */
    private function mockDbReturning(array $righe): DatabaseManager
    {
        $ondaConn = Mockery::mock(ConnectionInterface::class);
        $ondaConn->shouldReceive('select')->andReturn($righe);

        $defaultConn = Mockery::mock();
        $defaultConn->shouldReceive('transaction')->andReturnUsing(function ($cb) {
            return $cb();
        });

        $db = Mockery::mock(DatabaseManager::class);
        $db->shouldReceive('connection')->with('onda')->andReturn($ondaConn);
        $db->shouldReceive('connection')->withNoArgs()->andReturn($defaultConn);

        return $db;
    }

    private function riga(int $idDoc, string $commessa, string $codArt, float $qta, string $numero): object
    {
        return (object) [
            'IdDoc'           => $idDoc,
            'CodCommessa'     => $commessa,
            'CodArt'          => $codArt,
            'DataDocumento'   => '2026-05-01 00:00:00',
            'NumeroDocumento' => $numero,
            'Cliente'         => 'CLIENTE TEST',
            'Vettore'         => 'BRT',
            'QtaDDT'          => $qta,
        ];
    }

    /**
     * Stub minimale di Ordine: ha id e un metodo update() noop.
     */
    private function ordineStub(int $id): object
    {
        return new class($id) {
            public int $id;
            public function __construct(int $id) { $this->id = $id; }
            public function update(array $attrs): bool { return true; }
        };
    }
}
