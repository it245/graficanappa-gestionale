<?php

declare(strict_types=1);

namespace App\Modules\Macchine\Models;

/**
 * DTO/Value Object immutabile che descrive la configurazione di una macchina.
 *
 * Non eredita da Eloquent: vive in memoria, viene popolato dalle classi Rules
 * tramite MacchinaInterface::toConfig() e consumato da scheduler/servizi.
 */
final class MacchinaConfig
{
    /**
     * @param  string  $id  Codice univoco macchina (XL106, JOH, BOBST, ...)
     * @param  string  $nome  Nome esteso leggibile
     * @param  int  $orarioInizio  Ora di inizio turno feriale (0-24)
     * @param  int  $orarioFine  Ora di fine turno feriale (0-24, 24 = mezzanotte)
     * @param  bool  $lavoraSabato  True se la macchina lavora il sabato
     * @param  float  $oreSabato  Ore lavorative al sabato (0 se non lavora)
     * @param  float  $oreCambioConfig  Ore di setup tra config diverse (0 = nessun setup)
     * @param  int  $capacitaOraria  Fogli/ora nominali a regime
     * @param  array<string, mixed>  $meta  Metadati extra (config disponibili, note, ecc.)
     */
    public function __construct(
        public readonly string $id,
        public readonly string $nome,
        public readonly int $orarioInizio,
        public readonly int $orarioFine,
        public readonly bool $lavoraSabato,
        public readonly float $oreSabato,
        public readonly float $oreCambioConfig,
        public readonly int $capacitaOraria,
        public readonly array $meta = [],
    ) {
    }

    /**
     * Ore lavorative in un giorno feriale standard (lun-ven).
     */
    public function oreFeriali(): float
    {
        // Caso speciale 24h
        if ($this->orarioInizio === 0 && $this->orarioFine === 24) {
            return 24.0;
        }

        return (float) ($this->orarioFine - $this->orarioInizio);
    }

    /**
     * Ore lavorative settimanali (5 giorni feriali + eventuale sabato).
     */
    public function oreSettimanali(): float
    {
        return ($this->oreFeriali() * 5) + ($this->lavoraSabato ? $this->oreSabato : 0.0);
    }

    /**
     * Esporta la configurazione come array (utile per JSON/cache).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'orario_inizio' => $this->orarioInizio,
            'orario_fine' => $this->orarioFine,
            'lavora_sabato' => $this->lavoraSabato,
            'ore_sabato' => $this->oreSabato,
            'ore_cambio_config' => $this->oreCambioConfig,
            'capacita_oraria' => $this->capacitaOraria,
            'meta' => $this->meta,
        ];
    }
}
