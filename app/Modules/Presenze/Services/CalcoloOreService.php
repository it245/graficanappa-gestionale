<?php

declare(strict_types=1);

namespace App\Modules\Presenze\Services;

use App\Modules\Presenze\Contracts\TimbratureSourceInterface;
use App\Modules\Presenze\ValueObjects\BadgeOperatore;
use App\Modules\Presenze\ValueObjects\OreLavorate;
use App\Modules\Presenze\ValueObjects\PeriodoPresenza;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Calcolo ore lavorate giornaliere / settimanali / mensili.
 *
 * Sostituisce la logica inline di PresenzeController::index() lines
 * 127-183 (somma intervalli E→U, gestione periodo aperto su oggi).
 *
 * Politica pause: NON sottrae automaticamente la pausa pranzo standard
 * — sottrae solo le pause **timbrate** (gap tra una U e la E successiva
 * dello stesso operatore). Il MES non assume turni: mostra esattamente
 * ciò che ha registrato il badge.
 */
final class CalcoloOreService
{
    public function __construct(
        private readonly TimbratureSourceInterface $source,
    ) {}

    /**
     * Ore nette lavorate (somma durate periodi chiusi + periodo aperto
     * fino a now() se oggi).
     */
    public function oreGiornaliere(BadgeOperatore $badge, CarbonInterface $giorno): OreLavorate
    {
        $periodi = $this->source->timbratureDelGiorno($giorno)
            ->filter(fn (PeriodoPresenza $p) => $p->badge->equals($badge));

        $now = $giorno->copy()->isToday() ? Carbon::now() : null;
        $totale = OreLavorate::zero();
        foreach ($periodi as $p) {
            $totale = $totale->piu($p->durata($now));
        }

        return $totale;
    }

    /**
     * Lunedì → domenica della settimana che contiene $riferimento.
     */
    public function oreSettimanali(BadgeOperatore $badge, CarbonInterface $riferimento): OreLavorate
    {
        $start = $riferimento->copy()->startOfWeek();
        $end   = $riferimento->copy()->endOfWeek();
        return $this->oreNelRange($badge, $start, $end);
    }

    public function oreMensili(BadgeOperatore $badge, CarbonInterface $riferimento): OreLavorate
    {
        $start = $riferimento->copy()->startOfMonth();
        $end   = $riferimento->copy()->endOfMonth();
        return $this->oreNelRange($badge, $start, $end);
    }

    /**
     * Itera giorno per giorno (inclusivi). Inefficiente per range
     * lunghi ma ok per settimana/mese (max 31 query). Se in futuro
     * serve performance, aggiungere un metodo bulk all'adapter.
     */
    public function oreNelRange(BadgeOperatore $badge, CarbonInterface $start, CarbonInterface $end): OreLavorate
    {
        $totale = OreLavorate::zero();
        $cursor = $start->copy()->startOfDay();
        $finale = $end->copy()->startOfDay();

        while ($cursor->lte($finale)) {
            $totale = $totale->piu($this->oreGiornaliere($badge, $cursor));
            $cursor = $cursor->copy()->addDay();
        }

        return $totale;
    }

    /**
     * Pause timbrate del giorno (gap tra periodi consecutivi dello
     * stesso operatore). Restituisce 0 se solo un periodo.
     */
    public function pauseGiornaliere(BadgeOperatore $badge, CarbonInterface $giorno): OreLavorate
    {
        $periodi = $this->source->timbratureDelGiorno($giorno)
            ->filter(fn (PeriodoPresenza $p) => $p->badge->equals($badge))
            ->sortBy(fn (PeriodoPresenza $p) => $p->ingresso->getTimestamp())
            ->values();

        if ($periodi->count() < 2) {
            return OreLavorate::zero();
        }

        $totalePauseMin = 0;
        for ($i = 0; $i < $periodi->count() - 1; $i++) {
            $finePrec = $periodi[$i]->uscita;
            $inizioSucc = $periodi[$i + 1]->ingresso;
            if ($finePrec === null) {
                continue;
            }
            $totalePauseMin += (int) abs($finePrec->diffInMinutes($inizioSucc));
        }

        return OreLavorate::daMinuti($totalePauseMin);
    }
}
