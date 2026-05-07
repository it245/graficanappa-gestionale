<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Scheduling;

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Modules\Scheduling\Rules\UrgenzaRule;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Unit test della pure-function UrgenzaRule::urgenza().
 *
 * Niente container Laravel, niente DB: si instanziano i model Eloquent
 * in memoria e la relation `ordine` viene popolata via setRelation().
 */
final class UrgenzaRuleTest extends TestCase
{
    /**
     * @dataProvider provideScenariUrgenza
     */
    public function test_urgenza_giorni_a_consegna(
        int $giorniAvantiConsegna,
        float $atteso,
    ): void {
        $now = CarbonImmutable::create(2026, 5, 7, 9, 0, 0); // riferimento fisso

        $ordine = new Ordine();
        $ordine->data_prevista_consegna = $now->addDays($giorniAvantiConsegna)
            ->format('Y-m-d');

        $fase = new OrdineFase();
        $fase->setRelation('ordine', $ordine);

        $this->assertSame(
            $atteso,
            UrgenzaRule::urgenza($fase, $now),
            "giorniAvantiConsegna={$giorniAvantiConsegna} ⇒ atteso {$atteso}",
        );
    }

    /**
     * Scenari richiesti: deadline a 2gg / 5gg / 10gg + caso ritardo.
     *
     * @return array<string, array{0:int,1:float}>
     */
    public static function provideScenariUrgenza(): array
    {
        return [
            'consegna oggi'         => [0, 0.0],
            'consegna fra 2 giorni' => [2, 2.0],
            'consegna fra 5 giorni' => [5, 5.0],
            'consegna fra 10 giorni' => [10, 10.0],
            'in ritardo di 3 gg'    => [-3, -3.0],
        ];
    }

    public function test_ordine_senza_consegna_ritorna_max_float(): void
    {
        $fase = new OrdineFase();
        $fase->setRelation('ordine', new Ordine());

        $this->assertSame(PHP_FLOAT_MAX, UrgenzaRule::urgenza($fase));
    }

    public function test_e_urgente_dentro_finestra_5_giorni(): void
    {
        $now = CarbonImmutable::create(2026, 5, 7, 9, 0, 0);

        $ordine = new Ordine();
        $ordine->data_prevista_consegna = $now->addDays(3)->format('Y-m-d');

        $fase = new OrdineFase();
        $fase->setRelation('ordine', $ordine);

        // urgenza() base usa now() reale: passiamo un confronto manuale.
        $u = UrgenzaRule::urgenza($fase, $now);
        $this->assertLessThanOrEqual(5, $u);
        $this->assertGreaterThanOrEqual(0, $u);
    }

    public function test_fase_in_ritardo_e_urgente(): void
    {
        $now = CarbonImmutable::create(2026, 5, 7, 9, 0, 0);

        $ordine = new Ordine();
        $ordine->data_prevista_consegna = $now->subDays(2)->format('Y-m-d');

        $fase = new OrdineFase();
        $fase->setRelation('ordine', $ordine);

        $this->assertLessThan(0.0, UrgenzaRule::urgenza($fase, $now));
    }
}
