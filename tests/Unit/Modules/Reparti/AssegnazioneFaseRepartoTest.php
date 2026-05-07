<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Reparti;

use App\Modules\Reparti\Enums\CodiceReparto;
use App\Modules\Reparti\Rules\AssegnazioneFaseReparto;
use PHPUnit\Framework\TestCase;

/**
 * Unit test della pure-function AssegnazioneFaseReparto::reparto().
 * Nessuna dipendenza Laravel: niente container, niente DB.
 */
final class AssegnazioneFaseRepartoTest extends TestCase
{
    /**
     * @dataProvider provideMappingFaseReparto
     */
    public function test_fase_mappa_a_reparto_corretto(string $codiceFase, CodiceReparto $atteso): void
    {
        $this->assertSame(
            $atteso,
            AssegnazioneFaseReparto::reparto($codiceFase),
            "La fase {$codiceFase} avrebbe dovuto mappare a {$atteso->value}"
        );
    }

    /**
     * 5 esempi rappresentativi (uno per macro-area).
     *
     * @return array<string, array{0: string, 1: CodiceReparto}>
     */
    public static function provideMappingFaseReparto(): array
    {
        return [
            'offset XL106'         => ['STAMPAXL106',       CodiceReparto::STAMPA_OFFSET],
            'caldo JOH'            => ['STAMPACALDOJOH',    CodiceReparto::STAMPA_A_CALDO],
            'fustella BOBST'       => ['FUSTBOBST75X106',   CodiceReparto::FUSTELLA],
            'piegaincolla PI01'    => ['PI01',              CodiceReparto::PIEGAINCOLLA],
            'esterno EXT prefix'   => ['EXTSTAMPABUSTE.EST',CodiceReparto::ESTERNO],
        ];
    }

    public function test_fase_sconosciuta_fallback_a_produzione(): void
    {
        $this->assertSame(
            CodiceReparto::PRODUZIONE,
            AssegnazioneFaseReparto::reparto('FASE_INESISTENTE_XYZ')
        );
    }

    public function test_prefisso_est_lowercase_riconosciuto(): void
    {
        // "est " (lowercase) è prefisso usato da Onda per fasi conto-terzi
        $this->assertSame(
            CodiceReparto::ESTERNO,
            AssegnazioneFaseReparto::reparto('est FUSTBOBST75X106')
        );
    }
}
