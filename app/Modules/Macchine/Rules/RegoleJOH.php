<?php

declare(strict_types=1);

namespace App\Modules\Macchine\Rules;

use App\Modules\Macchine\Contracts\MacchinaInterface;
use App\Modules\Macchine\Models\MacchinaConfig;

/**
 * JOH - stampa a caldo (foil stamping).
 *
 * Collo di bottiglia n.1 della produzione Grafica Nappa.
 * - Lavora 6-22 lun-ven (2 turni) + sab 6-13
 * - Raccomandazione: introdurre 3 turno per liberare capacita
 * - Capacita: 1500 fogli/ora a regime
 */
final class RegoleJOH implements MacchinaInterface
{
    public function getId(): string
    {
        return 'JOH';
    }

    public function getNome(): string
    {
        return 'JOH - Stampa a Caldo';
    }

    public function getTurno(): string
    {
        return '6-22 lun-ven + sab 6-13 (collo bottiglia, 3 turno raccomandato)';
    }

    public function getCapacitaOraria(): int
    {
        return 1500;
    }

    public function richiedeCambioConfig(): bool
    {
        return false;
    }

    public function oreCambioConfig(): float
    {
        return 0.0;
    }

    public function toConfig(): MacchinaConfig
    {
        return new MacchinaConfig(
            id: $this->getId(),
            nome: $this->getNome(),
            orarioInizio: 6,
            orarioFine: 22,
            lavoraSabato: true,
            oreSabato: 7.0,
            oreCambioConfig: 0.0,
            capacitaOraria: $this->getCapacitaOraria(),
            meta: [
                'reparto' => 'stampa a caldo',
                'sequenza' => 30,
                'collo_bottiglia' => true,
                'raccomandazione' => 'introdurre terzo turno notturno',
            ],
        );
    }
}
