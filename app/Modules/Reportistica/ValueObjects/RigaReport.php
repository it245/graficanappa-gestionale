<?php

declare(strict_types=1);

namespace App\Modules\Reportistica\ValueObjects;

/**
 * Riga del Report Ore (1 commessa per riga).
 *
 * Sostituisce l'`(object)[...]` non tipizzato usato in
 * {@see \App\Http\Controllers\DashboardOwnerController::reportOre}.
 *
 * `fonteOre` = 'Prinect' | 'MES' | '' — preserva l'indicatore "P" voluto
 * dal capo (memoria sessione 9/03/2026).
 */
final class RigaReport
{
    public function __construct(
        public readonly string $commessa,
        public readonly string $cliente,
        public readonly string $descrizione,
        public readonly ?string $dataConsegna,
        public readonly float $orePreviste,
        public readonly float $oreEffettive,
        public readonly int $numFasi,
        public readonly int $numTerminate,
        public readonly string $fonteOre = '',
        public readonly ?float $qta = null,
    ) {}

    public function efficienza(): ?float
    {
        if ($this->oreEffettive <= 0.0) {
            return null;
        }
        return round(($this->orePreviste / $this->oreEffettive) * 100.0, 1);
    }

    public function toArray(): array
    {
        return [
            'commessa'      => $this->commessa,
            'cliente'       => $this->cliente,
            'descrizione'   => $this->descrizione,
            'data_consegna' => $this->dataConsegna,
            'ore_previste'  => $this->orePreviste,
            'ore_lavorate'  => $this->oreEffettive,
            'num_fasi'      => $this->numFasi,
            'num_terminate' => $this->numTerminate,
            'fonte_ore'     => $this->fonteOre,
            'qta'           => $this->qta,
            'efficienza'    => $this->efficienza(),
        ];
    }
}
