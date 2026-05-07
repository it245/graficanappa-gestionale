<?php

declare(strict_types=1);

namespace App\Modules\Stampa\Rules;

use App\Modules\Stampa\Enums\TipoStampa;

/**
 * Regola di calcolo del tempo di avviamento (setup) di una macchina di stampa.
 *
 * Tempi medi rilevati a Grafica Nappa:
 *  - Offset:   30 min base, +15 min se cambio carta
 *  - Digitale:  5 min (RIP + caricamento job)
 *  - Caldo:    60 min (clichè + foil setup)
 *  - Foil:     45 min (lamine, cambio bobina)
 *  - Plotter:  10 min
 *
 * Classe pura: nessuna dipendenza esterna, facilmente testabile in unit test.
 */
final class AvviamentoRule
{
    /**
     * Restituisce il tempo di avviamento in MINUTI per la tipologia data.
     *
     * @param  TipoStampa  $tipo         Tipologia di stampa.
     * @param  bool        $cambioCarta  Se true aggiunge la quota extra
     *                                   per cambio carta (rilevante solo
     *                                   per offset).
     */
    public function tempoAvviamento(TipoStampa $tipo, bool $cambioCarta = false): int
    {
        $base = match ($tipo) {
            TipoStampa::Offset   => 30,
            TipoStampa::Digitale => 5,
            TipoStampa::Caldo    => 60,
            TipoStampa::Foil     => 45,
            TipoStampa::Plotter  => 10,
        };

        if ($cambioCarta && $tipo === TipoStampa::Offset) {
            $base += 15;
        }

        return $base;
    }
}
