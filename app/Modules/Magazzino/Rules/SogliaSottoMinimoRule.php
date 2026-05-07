<?php

declare(strict_types=1);

namespace App\Modules\Magazzino\Rules;

use App\Models\MagazzinoArticolo;

/**
 * Regola: verifica se la giacenza totale di un articolo è scesa
 * sotto la `soglia_minima` configurata in anagrafica.
 *
 * Non modifica lo stato — solo predicato di lettura.
 *
 * @example
 *  $rule = new SogliaSottoMinimoRule();
 *  if ($rule->check($articolo)) {
 *      event(new SottoSogliaEvento($articolo, $articolo->giacenzaTotale()));
 *  }
 */
final class SogliaSottoMinimoRule
{
    /**
     * @param MagazzinoArticolo $articolo Articolo con relazione `giacenze` caricata o caricabile.
     * @return bool true se sotto soglia.
     */
    public function check(MagazzinoArticolo $articolo): bool
    {
        $soglia = (int) ($articolo->soglia_minima ?? 0);
        if ($soglia <= 0) {
            return false; // soglia non configurata = mai sotto soglia
        }

        $giacenzaTotale = (int) $articolo->giacenze()->sum('quantita');

        return $giacenzaTotale < $soglia;
    }

    /**
     * Calcola la differenza fra giacenza e soglia (negativa se sotto).
     */
    public function distanzaDallaSoglia(MagazzinoArticolo $articolo): int
    {
        $soglia = (int) ($articolo->soglia_minima ?? 0);
        $giacenza = (int) $articolo->giacenze()->sum('quantita');
        return $giacenza - $soglia;
    }
}
