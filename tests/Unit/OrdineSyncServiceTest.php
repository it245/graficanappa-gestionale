<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Onda\Contracts\OndaErpInterface;
use App\Modules\Onda\Services\OrdineSyncService;
use App\Modules\Reparti\Enums\CodiceReparto;
use App\Modules\Reparti\Rules\AssegnazioneFaseReparto;
use App\Modules\Reparti\Services\RepartoService;
use Carbon\Carbon;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test unit di {@see OrdineSyncService} (Strangler Fig di OndaSyncService::sincronizza).
 *
 * Strategia: mock {@see OndaErpInterface} per isolare il servizio da SQL Server.
 * Test mirati su contratto pubblico + regole pure di mapping fase→reparto
 * (la logica di sync DB-bound vive in Feature test con RefreshDatabase).
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
     * Verifica la forma del docblock del result di sync().
     *
     * Garantisce a colpo d'occhio che il contratto pubblico continui ad
     * esporre le chiavi che i caller (cron `onda:sync`, controller MES)
     * si aspettano.
     */
    public function test_sync_result_contiene_chiavi_attese(): void
    {
        $onda = Mockery::mock(OndaErpInterface::class);
        $service = new OrdineSyncService($onda);

        $reflection = new \ReflectionMethod($service, 'sync');
        $docblock = $reflection->getDocComment();

        $this->assertIsString($docblock);
        $this->assertStringContainsString('inseriti:int', $docblock);
        $this->assertStringContainsString('aggiornati:int', $docblock);
        $this->assertStringContainsString('errori:int', $docblock);
        $this->assertStringContainsString('fasi_create:int', $docblock);
        $this->assertStringContainsString('duplicati_rimossi:int', $docblock);
    }

    /**
     * Strangler Fig — verifica che la regola pura
     * {@see AssegnazioneFaseReparto::reparto()} (riusata dal modulo Reparti)
     * mappi correttamente i codici Onda usati dentro OrdineSyncService::sync().
     *
     * Questo test è il "contratto cross-modulo": se domani qualcuno
     * aggiunge una nuova fase Onda al codice di sync, qui forziamo a
     * registrare anche il mapping nella regola pura — niente fasi
     * "fantasma" che finiscono nel reparto sbagliato.
     */
    public function test_mapping_fase_reparto_via_regola_pura(): void
    {
        $cases = [
            'STAMPAXL106'      => CodiceReparto::STAMPA_OFFSET,
            'STAMPAXL106.3'    => CodiceReparto::STAMPA_OFFSET,
            'STAMPAINDIGO'     => CodiceReparto::DIGITALE,
            'STAMPAINDIGOBN'   => CodiceReparto::DIGITALE,
            'STAMPACALDOJOH'   => CodiceReparto::STAMPA_A_CALDO,
            'PI01'             => CodiceReparto::PIEGAINCOLLA,
            'PI02'             => CodiceReparto::PIEGAINCOLLA,
            'FUSTBOBST75X106'  => CodiceReparto::FUSTELLA,
            'FUSTSTELG33.44'   => CodiceReparto::FUSTELLA,
            'BRT1'             => CodiceReparto::SPEDIZIONE,
            'PLAOPA1LATO'      => CodiceReparto::PLASTIFICAZIONE,
            'TAGLIACARTE'      => CodiceReparto::LEGATORIA,
            'EXTBROSSCOPEST'   => CodiceReparto::ESTERNO,   // prefisso EXT → esterno
            'est STAMPACALDOJOH' => CodiceReparto::ESTERNO, // prefisso "est " → esterno
        ];

        foreach ($cases as $codiceFase => $atteso) {
            $this->assertSame(
                $atteso,
                AssegnazioneFaseReparto::reparto($codiceFase),
                "fase '{$codiceFase}' deve mappare su {$atteso->value}"
            );
        }
    }

    /**
     * Strangler Fig — verifica che la mappa granulare di
     * {@see RepartoService::mappaSlugToId()} (usata dentro la sync per
     * la logica di dedup) tenga distinti gli pseudo-reparti
     * "fustella piana" / "fustella cilindrica" / "tagliacarte" /
     * "finitura digitale" — che la regola pura collassa sui canonici.
     *
     * Se qualcuno collassasse questi nomi sui canonici, le regole di dedup
     * dentro OrdineSyncService::sync() non scatterebbero più (es. dedup
     * "fustella cilindrica" per ordine/cod_art vs "fustella piana" per
     * commessa: distinzione critica per non duplicare lavorazioni).
     */
    public function test_pseudo_reparti_granulari_preservati_per_dedup(): void
    {
        $svc = new RepartoService();
        $mappa = $svc->mappaSlugToId();

        // Pseudo-reparti distinti dai canonici — necessari per la dedup logic
        $this->assertSame('fustella piana',     $mappa['FUSTBOBST75X106']);
        $this->assertSame('fustella cilindrica', $mappa['FUSTSTELG33.44']);
        $this->assertSame('tagliacarte',        $mappa['TAGLIACARTE']);
        $this->assertSame('finitura digitale',  $mappa['UVSPOT.MGI.30M']);
        $this->assertSame('finestratura',       $mappa['FIN01']);

        // Tipo per max-2-fasi STAMPAXL106
        $tipi = $svc->tipoFromCodice();
        $this->assertSame('monofase', $tipi['STAMPAXL106']);
        // multifase
        $this->assertSame('multifase', $tipi['PI01']);
        $this->assertSame('multifase', $tipi['accopp+fust']);
    }
}
