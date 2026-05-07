<?php

declare(strict_types=1);

namespace App\Modules\Reparti\Services;

use App\Modules\Reparti\Enums\CodiceReparto;
use App\Modules\Reparti\Events\RepartoCapacitaSuperata;
use App\Modules\Reparti\ValueObjects\CapacitaReparto;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Event;

/**
 * Service applicativo: calcolo capacità di un reparto per giorno.
 *
 * Combina:
 *  - turni operativi (TurniService) → ore lavorabili in quella data
 *  - capacità "di targa" (CapacitaReparto VO) → macchine attive
 *
 * Output: ore disponibili per quella data (mai negative). Se l'allocazione
 * richiesta sfora la capacità, emette evento {@see RepartoCapacitaSuperata}.
 */
final class CapacitaService
{
    public function __construct(
        private readonly TurniService $turni,
    ) {}

    /**
     * Ore disponibili oggi per il reparto, considerando turni effettivi.
     *
     * Per i reparti H24 (offset/caldo) la capacità è già il monte-ore di
     * targa (24/16). Per gli altri reparti viene moltiplicata per il
     * numero di macchine attive.
     */
    public function oreDisponibiliGiorno(
        CodiceReparto $reparto,
        CarbonInterface $giorno,
        ?CapacitaReparto $capacita = null,
    ): int {
        $oreTurni = $this->turni->oreLavorativeGiorno($reparto, $giorno);
        if ($oreTurni === 0) {
            return 0; // festivo / domenica
        }

        $cap = $capacita ?? CapacitaReparto::defaultPerReparto($reparto);

        // Per macchine multiple: ore turno × macchine, capped a oreMacchina*macchine
        $oreEffettivePerMacchina = min($oreTurni, $cap->oreMacchina);
        return $oreEffettivePerMacchina * $cap->macchineAttive;
    }

    /**
     * Ore disponibili nella settimana lun-dom contenente $giorno.
     */
    public function oreDisponibiliSettimana(
        CodiceReparto $reparto,
        CarbonInterface $giorno,
        ?CapacitaReparto $capacita = null,
    ): int {
        $start = CarbonImmutable::instance($giorno)->startOfWeek();
        $totale = 0;
        for ($i = 0; $i < 7; $i++) {
            $totale += $this->oreDisponibiliGiorno($reparto, $start->addDays($i), $capacita);
        }
        return $totale;
    }

    /**
     * Verifica se l'allocazione richiesta supera la capacità del giorno.
     * In caso positivo emette {@see RepartoCapacitaSuperata}.
     *
     * @return bool true se OK (allocabile), false se ha superato la capacità.
     */
    public function verificaAllocazione(
        CodiceReparto $reparto,
        CarbonInterface $giorno,
        int $oreRichieste,
        ?CapacitaReparto $capacita = null,
    ): bool {
        $disponibili = $this->oreDisponibiliGiorno($reparto, $giorno, $capacita);
        if ($oreRichieste > $disponibili) {
            Event::dispatch(new RepartoCapacitaSuperata(
                reparto:        $reparto,
                giorno:         CarbonImmutable::instance($giorno),
                oreRichieste:   $oreRichieste,
                oreDisponibili: $disponibili,
            ));
            return false;
        }
        return true;
    }
}
