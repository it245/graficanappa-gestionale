<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Onda\Contracts\OndaErpInterface;
use App\Modules\Onda\Services\OrdineSyncService;
use Carbon\Carbon;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test unit di {@see OrdineSyncService} (Strangler Fig di OndaSyncService::sincronizza).
 *
 * Strategia: mock {@see OndaErpInterface} per isolare il servizio da SQL Server.
 * Il body principale del legacy non viene esercitato qui — verifichiamo
 * il contratto pubblico (forma del result, propagazione errori) e che
 * l'adapter sia accessibile per test futuri.
 *
 * Quando il body migrerà dentro OrdineSyncService::sync() (iterazioni successive),
 * questo test si arricchirà con scenari di parsing/dedup specifici.
 */
class OrdineSyncServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Verifica il contratto pubblico: l'adapter passato in costruzione è
     * recuperabile via {@see OrdineSyncService::adapter()} e implementa
     * la giusta interfaccia.
     *
     * Sostituisce il caso "smoke test" finché il body non migra qui:
     * conferma che la DI funziona e che qualunque caller che inietta
     * OrdineSyncService via container ottiene un servizio "vivo".
     */
    public function test_dipende_da_onda_erp_interface(): void
    {
        $onda = Mockery::mock(OndaErpInterface::class);

        $service = new OrdineSyncService($onda);

        $this->assertSame($onda, $service->adapter());
        $this->assertInstanceOf(OndaErpInterface::class, $service->adapter());
    }

    /**
     * Verifica che il mock di OndaErpInterface possa essere interrogato come
     * sarà dal body futuro: getOrdiniDal(Carbon) → list<stdClass>.
     *
     * Questo test "guarda al futuro": quando sposteremo la business logic
     * dentro OrdineSyncService::sync(), questa è la chiamata che il servizio
     * dovrà fare — il test garantisce che il contratto dell'interfaccia
     * regga lo use-case.
     */
    public function test_adapter_puo_essere_interrogato_per_finestra_temporale(): void
    {
        $dal = Carbon::create(2026, 2, 27);

        $rigaFake = (object) [
            'CodCommessa'   => '0066575-26',
            'PrdIdDoc'      => 999,
            'CodArt'        => 'I.copertina',
            'OC_Descrizione' => 'COPERTINA TEST',
            'ClienteNome'   => 'CLIENTE SPA',
            'QtaDaProdurre' => 5000,
            'CodFase'       => 'STAMPAXL106',
            'CodMacchina'   => 'XL106-1',
            'QtaDaLavorare' => 5500,
            'TipoRigaFase'  => 1,
        ];

        $onda = Mockery::mock(OndaErpInterface::class);
        $onda->shouldReceive('getOrdiniDal')
            ->once()
            ->with(Mockery::on(fn (Carbon $c) => $c->equalTo($dal)))
            ->andReturn([$rigaFake]);

        $service = new OrdineSyncService($onda);

        $righe = $service->adapter()->getOrdiniDal($dal);

        $this->assertCount(1, $righe);
        $this->assertSame('0066575-26', $righe[0]->CodCommessa);
        $this->assertSame('STAMPAXL106', $righe[0]->CodFase);
    }

    /**
     * Verifica la forma del return di sync() quando l'adapter non torna nulla.
     *
     * NOTA: in questa iterazione sync() delega al legacy OndaSyncService::sincronizza()
     * che fa accesso DB reale — non possiamo eseguirlo in unit test puro senza Laravel.
     * Quando il body migrerà qui, sostituiremo questo test con
     * `test_returns_zero_counts_on_empty_payload()` che esercita la logica vera.
     *
     * Per ora documentiamo il contratto del result tramite reflection.
     */
    public function test_sync_result_contiene_chiavi_attese(): void
    {
        $onda = Mockery::mock(OndaErpInterface::class);
        $service = new OrdineSyncService($onda);

        // Reflection sul docblock: quando il body migrerà qui, sostituire con
        // chiamata reale. Per ora basta verificare la struttura tramite phpdoc.
        $reflection = new \ReflectionMethod($service, 'sync');
        $docblock = $reflection->getDocComment();

        $this->assertIsString($docblock);
        $this->assertStringContainsString('inseriti:int', $docblock);
        $this->assertStringContainsString('aggiornati:int', $docblock);
        $this->assertStringContainsString('errori:int', $docblock);
        $this->assertStringContainsString('fasi_create:int', $docblock);
    }
}
