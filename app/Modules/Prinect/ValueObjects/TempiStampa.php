<?php

declare(strict_types=1);

namespace App\Modules\Prinect\ValueObjects;

use InvalidArgumentException;

/**
 * Tempi macchina di un workstep di stampa offset.
 *
 * Tempo di avviamento: dalla messa in macchina al primo foglio buono
 * (lastra, registro, cala inchiostro). Tipicamente 20-60 min su XL106.
 *
 * Tempo di esecuzione: durata effettiva della tiratura, foglio buono dopo
 * foglio buono. Esclude le pause registrate a parte.
 *
 * Entrambi in secondi per allineamento con le colonne DB
 * `tempo_avviamento_sec` / `tempo_esecuzione_sec` su `ordine_fasi`.
 */
final class TempiStampa
{
    public function __construct(
        public readonly int $avviamentoSec,
        public readonly int $esecuzioneSec,
    ) {
        if ($avviamentoSec < 0 || $esecuzioneSec < 0) {
            throw new InvalidArgumentException('TempiStampa: tempi negativi non ammessi.');
        }
    }

    public static function zero(): self
    {
        return new self(0, 0);
    }

    public function totaleSec(): int
    {
        return $this->avviamentoSec + $this->esecuzioneSec;
    }

    public function avviamentoMin(): float
    {
        return round($this->avviamentoSec / 60, 1);
    }

    public function esecuzioneMin(): float
    {
        return round($this->esecuzioneSec / 60, 1);
    }

    /**
     * Somma due tempi (utile per aggregare attività multiple).
     */
    public function piu(self $other): self
    {
        return new self(
            $this->avviamentoSec + $other->avviamentoSec,
            $this->esecuzioneSec + $other->esecuzioneSec,
        );
    }
}
