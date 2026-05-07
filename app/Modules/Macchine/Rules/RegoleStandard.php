<?php

declare(strict_types=1);

namespace App\Modules\Macchine\Rules;

use App\Modules\Macchine\Contracts\MacchinaInterface;
use App\Modules\Macchine\Models\MacchinaConfig;

/**
 * Regole "standard" 6-22 lun-ven, usate per tutte le macchine
 * senza vincoli particolari (STEL, PLAST, FIN, INDIGO, TAGLIO,
 * LEGAT, ZUND, MGI).
 *
 * Costruita con id/nome/capacita parametrici, cosi da evitare
 * di duplicare 9 classi quasi identiche.
 */
final class RegoleStandard implements MacchinaInterface
{
    /**
     * @param  array<string, mixed>  $meta  Reparto, sequenza, note
     */
    public function __construct(
        private readonly string $id,
        private readonly string $nome,
        private readonly int $capacitaOraria,
        private readonly array $meta = [],
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getTurno(): string
    {
        return '6-22 lun-ven';
    }

    public function getCapacitaOraria(): int
    {
        return $this->capacitaOraria;
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
            id: $this->id,
            nome: $this->nome,
            orarioInizio: 6,
            orarioFine: 22,
            lavoraSabato: false,
            oreSabato: 0.0,
            oreCambioConfig: 0.0,
            capacitaOraria: $this->capacitaOraria,
            meta: $this->meta,
        );
    }
}
