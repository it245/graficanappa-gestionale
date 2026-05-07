<?php

declare(strict_types=1);

namespace App\Modules\Presenze\ValueObjects;

use Carbon\CarbonInterface;
use Carbon\Carbon;

/**
 * Periodo di presenza chiuso (ingresso → uscita) o aperto
 * (ingresso senza uscita = operatore ancora dentro).
 *
 * Convenzione NetTime: una E senza U a fine giornata = badge
 * dimenticato; una E seguita da E con gap > 2h = rientro pausa.
 * Questa logica resta in CalcoloOreService/Adapter — qui il VO è
 * solo dato strutturato.
 */
final readonly class PeriodoPresenza
{
    public function __construct(
        public BadgeOperatore $badge,
        public CarbonInterface $ingresso,
        public ?CarbonInterface $uscita = null,
    ) {
        if ($this->uscita !== null && $this->uscita->lt($this->ingresso)) {
            throw new \InvalidArgumentException(
                "Uscita ({$this->uscita}) precedente all'ingresso ({$this->ingresso}) per badge {$this->badge}"
            );
        }
    }

    public function isAperto(): bool
    {
        return $this->uscita === null;
    }

    /**
     * Durata del periodo. Se ancora aperto e $now passato, calcola
     * fino a $now (default = adesso). Su periodi aperti del passato
     * (giornate non chiuse correttamente) ritorna 0.
     */
    public function durata(?CarbonInterface $now = null): OreLavorate
    {
        $fine = $this->uscita;
        if ($fine === null) {
            $now = $now ?? Carbon::now();
            // Periodo aperto solo se l'ingresso è oggi: altrimenti
            // è un badge dimenticato e non possiamo stimare la durata.
            if (!$this->ingresso->isSameDay($now)) {
                return OreLavorate::zero();
            }
            $fine = $now;
        }

        $minuti = (int) abs($this->ingresso->diffInMinutes($fine));
        return OreLavorate::daMinuti($minuti);
    }

    public function intersecaGiorno(CarbonInterface $giorno): bool
    {
        $start = $giorno->copy()->startOfDay();
        $end = $giorno->copy()->endOfDay();
        $fine = $this->uscita ?? $end;
        return $this->ingresso->lte($end) && $fine->gte($start);
    }
}
