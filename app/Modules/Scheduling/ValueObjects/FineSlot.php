<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\ValueObjects;

use Carbon\CarbonImmutable;

/**
 * Slot temporale finalizzato su una macchina.
 *
 * DTO immutabile usato da {@see SchedulerEngineInterface::schedula()}
 * per comunicare il piano produttivo (cf. campi DB
 * `sched_macchina`, `sched_inizio`, `sched_fine`).
 */
final readonly class FineSlot
{
    public function __construct(
        public string $macchina,
        public CarbonImmutable $inizio,
        public CarbonImmutable $fine,
        public float $durataOre,
    ) {
    }

    public static function da(
        string $macchina,
        CarbonImmutable $inizio,
        CarbonImmutable $fine,
    ): self {
        $ore = $inizio->diffInMinutes($fine) / 60.0;

        return new self($macchina, $inizio, $fine, round($ore, 2));
    }

    public function toArray(): array
    {
        return [
            'macchina'    => $this->macchina,
            'inizio'      => $this->inizio->toIso8601String(),
            'fine'        => $this->fine->toIso8601String(),
            'durata_ore'  => $this->durataOre,
        ];
    }
}
