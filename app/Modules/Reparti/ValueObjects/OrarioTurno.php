<?php

declare(strict_types=1);

namespace App\Modules\Reparti\ValueObjects;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Value Object immutabile che rappresenta un turno di lavoro standard.
 *
 * Convenzione Grafica Nappa:
 *  - Turno 1 (mattina):    06:00 → 14:00
 *  - Turno 2 (pomeriggio): 14:00 → 22:00
 *  - Turno 3 (notte):      22:00 → 06:00 (giorno successivo)
 *
 * Le ore sono sempre espresse come "ora del giorno" 0..23 (ammesso 24
 * solo come marker di mezzanotte fine-turno, normalizzato internamente).
 */
final class OrarioTurno
{
    public const TURNO_MATTINA    = 1;
    public const TURNO_POMERIGGIO = 2;
    public const TURNO_NOTTE      = 3;

    public function __construct(
        public readonly int $oraInizio,
        public readonly int $oraFine,
        public readonly int $turno,
    ) {
        if ($turno < 1 || $turno > 3) {
            throw new InvalidArgumentException("Turno non valido: {$turno} (atteso 1..3).");
        }
        if ($oraInizio < 0 || $oraInizio > 23) {
            throw new InvalidArgumentException("oraInizio fuori range: {$oraInizio}.");
        }
        if ($oraFine < 0 || $oraFine > 24) {
            throw new InvalidArgumentException("oraFine fuori range: {$oraFine}.");
        }
    }

    public static function mattina(): self
    {
        return new self(6, 14, self::TURNO_MATTINA);
    }

    public static function pomeriggio(): self
    {
        return new self(14, 22, self::TURNO_POMERIGGIO);
    }

    public static function notte(): self
    {
        return new self(22, 6, self::TURNO_NOTTE);
    }

    /**
     * Restituisce [inizio, fine] come CarbonImmutable per il giorno fornito.
     * Il turno notte sfora a giorno+1 sull'estremo `fine`.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function oreInGiorno(CarbonInterface $giorno, ?int $turno = null): array
    {
        $base = CarbonImmutable::instance($giorno)->startOfDay();
        $inizio = $base->setTime($this->oraInizio, 0, 0);
        $fine   = $this->oraFine <= $this->oraInizio
            ? $base->addDay()->setTime($this->oraFine, 0, 0)
            : $base->setTime($this->oraFine === 24 ? 0 : $this->oraFine, 0, 0);

        // turno argomento ignorato: VO già descrive uno specifico turno;
        // parametro mantenuto per compatibilità API richiesta.
        unset($turno);

        return [$inizio, $fine];
    }

    /**
     * Durata del turno in ore intere (8 per i turni standard).
     */
    public function durataOre(): int
    {
        if ($this->oraFine > $this->oraInizio) {
            return $this->oraFine - $this->oraInizio;
        }
        // turno che attraversa la mezzanotte
        return (24 - $this->oraInizio) + $this->oraFine;
    }
}
