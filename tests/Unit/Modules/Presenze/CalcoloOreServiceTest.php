<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Presenze;

use App\Modules\Presenze\Adapters\ManualeAdapter;
use App\Modules\Presenze\Services\CalcoloOreService;
use App\Modules\Presenze\ValueObjects\BadgeOperatore;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Test CalcoloOreService usando ManualeAdapter come sorgente
 * deterministica (niente DB, niente filesystem).
 *
 * Scenari coperti:
 *  - Giornata standard: ingresso 8 / uscita 17 = 9h
 *  - Pausa pranzo timbrata 12-13: 8h nette di lavoro
 *  - Range settimanale: somma su più giorni
 *  - Periodo aperto su giorno passato (badge dimenticato): 0h
 */
class CalcoloOreServiceTest extends TestCase
{
    private function badge(): BadgeOperatore
    {
        return BadgeOperatore::da('123');
    }

    public function test_ingresso_8_uscita_17_da_9_ore(): void
    {
        $adapter = ManualeAdapter::da([[
            'badge' => '000123',
            'ingresso' => '2026-05-07 08:00:00',
            'uscita'   => '2026-05-07 17:00:00',
        ]]);
        $service = new CalcoloOreService($adapter);

        $ore = $service->oreGiornaliere($this->badge(), Carbon::parse('2026-05-07'));

        $this->assertSame(9 * 60, $ore->minutiTotali);
        $this->assertSame(9, $ore->ore());
        $this->assertSame(0, $ore->minuti());
    }

    public function test_pausa_pranzo_12_13_da_8_ore_nette(): void
    {
        $adapter = ManualeAdapter::da([
            [
                'badge' => '000123',
                'ingresso' => '2026-05-07 08:00:00',
                'uscita'   => '2026-05-07 12:00:00',
            ],
            [
                'badge' => '000123',
                'ingresso' => '2026-05-07 13:00:00',
                'uscita'   => '2026-05-07 17:00:00',
            ],
        ]);
        $service = new CalcoloOreService($adapter);

        $ore = $service->oreGiornaliere($this->badge(), Carbon::parse('2026-05-07'));
        $pause = $service->pauseGiornaliere($this->badge(), Carbon::parse('2026-05-07'));

        $this->assertSame(8 * 60, $ore->minutiTotali, 'Lavoro netto = 4h + 4h = 8h');
        $this->assertSame(60, $pause->minutiTotali, 'Pausa pranzo = 1h');
    }

    public function test_periodo_aperto_su_giorno_passato_da_zero(): void
    {
        // Operatore ha dimenticato il badge: E 8:00 senza U.
        // Su giorno passato (non oggi) la durata = 0 (non possiamo stimare).
        $ieri = Carbon::yesterday()->format('Y-m-d');
        $adapter = ManualeAdapter::da([[
            'badge' => '000123',
            'ingresso' => $ieri . ' 08:00:00',
            // niente uscita
        ]]);
        $service = new CalcoloOreService($adapter);

        $ore = $service->oreGiornaliere($this->badge(), Carbon::parse($ieri));

        $this->assertSame(0, $ore->minutiTotali);
    }

    public function test_range_settimanale_somma_giornate(): void
    {
        $adapter = ManualeAdapter::da([
            ['badge' => '000123', 'ingresso' => '2026-05-04 08:00', 'uscita' => '2026-05-04 17:00'], // lun 9h
            ['badge' => '000123', 'ingresso' => '2026-05-05 08:00', 'uscita' => '2026-05-05 17:00'], // mar 9h
            ['badge' => '000123', 'ingresso' => '2026-05-06 08:00', 'uscita' => '2026-05-06 13:00'], // mer 5h
        ]);
        $service = new CalcoloOreService($adapter);

        $ore = $service->oreSettimanali($this->badge(), Carbon::parse('2026-05-06'));
        // Settimana = lun 4 → dom 10 maggio 2026: 9 + 9 + 5 = 23h
        $this->assertSame(23 * 60, $ore->minutiTotali);
    }

    public function test_filtra_solo_il_badge_richiesto(): void
    {
        $adapter = ManualeAdapter::da([
            ['badge' => '000123', 'ingresso' => '2026-05-07 08:00', 'uscita' => '2026-05-07 17:00'],
            ['badge' => '000456', 'ingresso' => '2026-05-07 09:00', 'uscita' => '2026-05-07 18:00'],
        ]);
        $service = new CalcoloOreService($adapter);

        $ore123 = $service->oreGiornaliere(BadgeOperatore::da('123'), Carbon::parse('2026-05-07'));
        $ore456 = $service->oreGiornaliere(BadgeOperatore::da('456'), Carbon::parse('2026-05-07'));

        $this->assertSame(9 * 60, $ore123->minutiTotali);
        $this->assertSame(9 * 60, $ore456->minutiTotali);
    }
}
