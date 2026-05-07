<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Reportistica;

use App\Modules\Reportistica\Rules\EfficienzaRule;
use PHPUnit\Framework\TestCase;

/**
 * Unit test pure-function EfficienzaRule.
 *
 * 5 scenari ratio: ottimo / ok / warn / critical / na.
 * Nessuna dipendenza Laravel: niente container, niente DB.
 */
final class EfficienzaRuleTest extends TestCase
{
    public function test_efficienza_ottima_sotto_budget(): void
    {
        // 8h previste, 6h effettive → 133.3% (ben sotto budget)
        $eff = EfficienzaRule::calcola(8.0, 6.0);

        $this->assertSame(133.3, $eff);
        $this->assertSame('ok', EfficienzaRule::badge($eff));
    }

    public function test_efficienza_ok_a_pari(): void
    {
        // 10h previste, 10h effettive → 100%
        $eff = EfficienzaRule::calcola(10.0, 10.0);

        $this->assertSame(100.0, $eff);
        $this->assertSame('ok', EfficienzaRule::badge($eff));
    }

    public function test_efficienza_warn_lieve_sforatura(): void
    {
        // 8h previste, 10h effettive → 80% (zona gialla 70-90)
        $eff = EfficienzaRule::calcola(8.0, 10.0);

        $this->assertSame(80.0, $eff);
        $this->assertSame('warn', EfficienzaRule::badge($eff));
    }

    public function test_efficienza_critica_sforatura_grave(): void
    {
        // 5h previste, 10h effettive → 50% (zona rossa <70)
        $eff = EfficienzaRule::calcola(5.0, 10.0);

        $this->assertSame(50.0, $eff);
        $this->assertSame('critical', EfficienzaRule::badge($eff));
    }

    public function test_efficienza_na_quando_ore_effettive_zero(): void
    {
        // Fase non ancora lavorata → null (non-applicabile)
        $eff = EfficienzaRule::calcola(8.0, 0.0);

        $this->assertNull($eff);
        $this->assertSame('na', EfficienzaRule::badge($eff));
    }
}
