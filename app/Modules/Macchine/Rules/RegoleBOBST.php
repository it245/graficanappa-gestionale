<?php

declare(strict_types=1);

namespace App\Modules\Macchine\Rules;

use App\Modules\Macchine\Contracts\MacchinaInterface;
use App\Modules\Macchine\Models\MacchinaConfig;

/**
 * BOBST - fustella piana.
 *
 * - 1 sola macchina, 6-22 lun-ven
 * - 2 configurazioni: rilievi (sequenza 39) e fustelle (sequenza 40)
 * - Cambio tra config = 1h, lo scheduler raggruppa job consecutivi
 *   stessa config per minimizzare setup
 * - Capacita: 2200 fogli/ora a regime
 */
final class RegoleBOBST implements MacchinaInterface
{
    public function getId(): string
    {
        return 'BOBST';
    }

    public function getNome(): string
    {
        return 'BOBST - Fustella Piana';
    }

    public function getTurno(): string
    {
        return '6-22 lun-ven (cambio config 1h tra rilievi/fustelle)';
    }

    public function getCapacitaOraria(): int
    {
        return 2200;
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
                'reparto' => 'fustella piana',
                'configurazioni' => ['rilievi' => 39, 'fustelle' => 40],
                'unita' => 1,
            ],
        );
    }
}
