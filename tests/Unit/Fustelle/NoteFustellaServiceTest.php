<?php

declare(strict_types=1);

namespace Tests\Unit\Fustelle;

use App\Models\OrdineFase;
use App\Modules\Fustelle\Events\NotaFustellaAggiunta;
use App\Modules\Fustelle\Services\NoteFustellaService;
use Illuminate\Support\Facades\Event;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Test unit del NoteFustellaService.
 *
 * Strategia: usa overload mock di OrdineFase per intercettare save() senza DB.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class NoteFustellaServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_aggiungi_appende_nota_a_campo_vuoto(): void
    {
        Event::fake([NotaFustellaAggiunta::class]);

        /** @var OrdineFase&\Mockery\MockInterface $fase */
        $fase = Mockery::mock(OrdineFase::class);
        $fase->note = null;
        $fase->shouldReceive('save')->once()->andReturn(true);

        $service = new NoteFustellaService();
        $nota = $service->aggiungi($fase, 'Mirko', 'cambiata lama');

        $this->assertSame('Mirko', $nota->autore);
        $this->assertSame('cambiata lama', $nota->testo);
        $this->assertNotNull($fase->note);
        $this->assertStringContainsString('[Mirko -', (string) $fase->note);
        $this->assertStringContainsString('] cambiata lama', (string) $fase->note);

        Event::assertDispatched(NotaFustellaAggiunta::class);
    }

    public function test_aggiungi_preserva_storico_senza_sovrascrivere(): void
    {
        Event::fake([NotaFustellaAggiunta::class]);

        $precedente = '[Mirko - 23/04 14:30] prima nota';

        /** @var OrdineFase&\Mockery\MockInterface $fase */
        $fase = Mockery::mock(OrdineFase::class);
        $fase->note = $precedente;
        $fase->shouldReceive('save')->once()->andReturn(true);

        $service = new NoteFustellaService();
        $service->aggiungi($fase, 'Vittorio', 'seconda nota');

        $this->assertStringContainsString($precedente, (string) $fase->note);
        $this->assertStringContainsString('[Vittorio -', (string) $fase->note);
        $this->assertStringContainsString('] seconda nota', (string) $fase->note);
        // Nuova nota dopo la precedente (storico in append)
        $this->assertGreaterThan(
            strpos((string) $fase->note, 'prima nota'),
            strpos((string) $fase->note, 'seconda nota'),
        );
    }

    public function test_elenco_estrae_note_in_formato_canonico(): void
    {
        /** @var OrdineFase&\Mockery\MockInterface $fase */
        $fase = Mockery::mock(OrdineFase::class);
        $fase->note = "[Mirko - 23/04 14:30] prima nota\n"
                    . "testo libero non riconosciuto\n"
                    . "[Vittorio - 24/04 09:15] seconda nota";

        $service = new NoteFustellaService();
        $elenco = $service->elenco($fase);

        $this->assertCount(2, $elenco);
        $this->assertSame('Mirko', $elenco[0]['autore']);
        $this->assertSame('23/04 14:30', $elenco[0]['timestamp']);
        $this->assertSame('prima nota', $elenco[0]['testo']);
        $this->assertSame('Vittorio', $elenco[1]['autore']);
        $this->assertSame('seconda nota', $elenco[1]['testo']);
    }

    public function test_elenco_su_note_vuote_ritorna_array_vuoto(): void
    {
        /** @var OrdineFase&\Mockery\MockInterface $fase */
        $fase = Mockery::mock(OrdineFase::class);
        $fase->note = null;

        $service = new NoteFustellaService();
        $this->assertSame([], $service->elenco($fase));
    }

    public function test_aggiungi_lancia_su_testo_vuoto(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        /** @var OrdineFase&\Mockery\MockInterface $fase */
        $fase = Mockery::mock(OrdineFase::class);
        $fase->note = '';

        $service = new NoteFustellaService();
        $service->aggiungi($fase, 'Mirko', '   ');
    }
}
