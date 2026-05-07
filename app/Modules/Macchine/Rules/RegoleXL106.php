<?php

declare(strict_types=1);

namespace App\Modules\Macchine\Rules;

use App\Modules\Macchine\Contracts\MacchinaInterface;
use App\Modules\Macchine\Models\MacchinaConfig;

/**
 * Heidelberg XL106 - stampa offset.
 *
 * - Funziona 24h lun-ven (3 turni continui)
 * - 1 sola macchina in linea
 * - Capacita nominale: 4500 fogli/ora a regime (8h/h pulizie escluse)
 * - Nessun cambio configurazione strutturale (ogni job ha proprio avviamento)
 */
final class RegoleXL106 implements MacchinaInterface
{
    public function getId(): string
    {
        return 'XL106';
    }

    public function getNome(): string
    {
        return 'Heidelberg XL106 - Stampa Offset';
    }

    public function getTurno(): string
    {
        return '24h lun-ven';
    }

    public function getCapacitaOraria(): int
    {
        return 4500;
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
            orarioInizio: 0,
            orarioFine: 24,
            lavoraSabato: false,
            oreSabato: 0.0,
            oreCambioConfig: 0.0,
            capacitaOraria: $this->getCapacitaOraria(),
            meta: [
                'reparto' => 'stampa offset',
                'sequenza' => 10,
                'unita' => 1,
            ],
        );
    }
}
