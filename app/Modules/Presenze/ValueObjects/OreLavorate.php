<?php

declare(strict_types=1);

namespace App\Modules\Presenze\ValueObjects;

/**
 * Quantità di lavoro espressa in ore + minuti.
 *
 * Internamente memorizzata in minuti totali (intero) per evitare
 * problemi di arrotondamento float comuni nei calcoli orari.
 *
 * Range valido: 0 .. 7 * 24 * 60 (settimana intera). Oltre lancia
 * eccezione perché indica quasi sicuramente bug di parsing.
 */
final readonly class OreLavorate
{
    private const MAX_MINUTI = 7 * 24 * 60; // 1 settimana

    public function __construct(public int $minutiTotali)
    {
        if ($this->minutiTotali < 0) {
            throw new \InvalidArgumentException("Minuti negativi non ammessi: {$this->minutiTotali}");
        }
        if ($this->minutiTotali > self::MAX_MINUTI) {
            throw new \InvalidArgumentException(
                "Minuti totali oltre il massimo settimanale ({$this->minutiTotali} > " . self::MAX_MINUTI . ')'
            );
        }
    }

    public static function daMinuti(int $minuti): self
    {
        return new self($minuti);
    }

    public static function daOreMinuti(int $ore, int $minuti = 0): self
    {
        return new self($ore * 60 + $minuti);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function ore(): int
    {
        return intdiv($this->minutiTotali, 60);
    }

    public function minuti(): int
    {
        return $this->minutiTotali % 60;
    }

    public function oreDecimali(): float
    {
        return round($this->minutiTotali / 60, 2);
    }

    public function piu(self $altro): self
    {
        return new self($this->minutiTotali + $altro->minutiTotali);
    }

    public function meno(self $altro): self
    {
        return new self(max(0, $this->minutiTotali - $altro->minutiTotali));
    }

    /** Etichetta umana "8h 30m" (o "30m" se < 1h, o "8h" se rotondo). */
    public function label(): string
    {
        $h = $this->ore();
        $m = $this->minuti();
        if ($h === 0) {
            return "{$m}m";
        }
        if ($m === 0) {
            return "{$h}h";
        }
        return "{$h}h {$m}m";
    }

    public function __toString(): string
    {
        return $this->label();
    }
}
