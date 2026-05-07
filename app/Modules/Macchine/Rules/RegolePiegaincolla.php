<?php

declare(strict_types=1);

namespace App\Modules\Macchine\Rules;

use App\Modules\Macchine\Contracts\MacchinaInterface;
use App\Modules\Macchine\Models\MacchinaConfig;

/**
 * Piegaincolla - reparto piega/incolla cartotecnica.
 *
 * - 1 sola macchina, 6-22 lun-ven
 * - 3 configurazioni: PI01 / PI02 / PI03
 * - Cambio tra config = 1h, scheduler crea blocchi consecutivi
 *   stessa config per ridurre downtime
 * - Capacita: 4000 fogli/ora a regime
 */
final class RegolePiegaincolla implements MacchinaInterface
{
    public function getId(): string
    {
        return 'PIEGA';
    }

    public function getNome(): string
    {
        return 'Piegaincolla (PI01/PI02/PI03)';
    }

    public function getTurno(): string
    {
        return '6-22 lun-ven (cambio config 1h tra PI01/PI02/PI03)';
    }

    public function getCapacitaOraria(): int
    {
        return 4000;
    }

    public function richiedeCambioConfig(): bool
    {
        return true;
    }

    public function oreCambioConfig(): float
    {
        return 1.0;
    }

    public function toConfig(): MacchinaConfig
    {
        return new MacchinaConfig(
            id: $this->getId(),
            nome: $this->getNome(),
            orarioInizio: 6,
            orarioFine: 22,
            lavoraSabato: false,
            oreSabato: 0.0,
            oreCambioConfig: $this->oreCambioConfig(),
            capacitaOraria: $this->getCapacitaOraria(),
            meta: [
                'reparto' => 'piegaincolla',
                'configurazioni' => ['PI01', 'PI02', 'PI03'],
                'sequenza' => 110,
                'unita' => 1,
            ],
        );
    }
}
