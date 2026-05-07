<?php

declare(strict_types=1);

namespace App\Modules\Commessa\Rules;

use App\Modules\Commessa\Enums\StatoCommessa;
use Illuminate\Support\Collection;

/**
 * Logica aggregata pure: deriva lo stato di una commessa dalla collection
 * di fasi (ordine_fasi). Nessuna query DB qui dentro.
 *
 * Gli oggetti $fasi devono esporre la proprietà/attributo `stato` (int 0-4).
 *
 * Stati delle fasi:
 *   0 = caricato, 1 = pronto, 2 = avviato, 3 = terminato, 4 = consegnato
 *
 * Stato della commessa:
 *   - Aperta: nessuna fase avviata (tutte 0/1)
 *   - InCorso: almeno una fase avviata (2), ma non tutte completate
 *   - CompletataInternamente: tutte >= 3, nessuna a 4
 *   - ConsegnataParziale: alcune a 4, ma non tutte
 *   - ConsegnataTotale: tutte a 4
 *
 * @example
 *   $rule = new StatoDerivatoRule();
 *   $stato = $rule->calcolaStato($commessa->fasi); // StatoCommessa::InCorso
 */
final class StatoDerivatoRule
{
    /**
     * @param Collection<int, object> $fasi Collection di OrdineFase (o stdClass con ->stato)
     */
    public function calcolaStato(Collection $fasi): StatoCommessa
    {
        if ($fasi->isEmpty()) {
            return StatoCommessa::Aperta;
        }

        $stati = $fasi->map(fn ($f) => $this->statoIntero($f))->all();

        $tutte4 = $this->tuttiUguali($stati, 4);
        if ($tutte4) {
            return StatoCommessa::ConsegnataTotale;
        }

        $almenoUna4 = in_array(4, $stati, true);
        $tutteMin3 = $this->tuttiAlmeno($stati, 3);

        if ($almenoUna4 && !$tutte4) {
            // Almeno una consegnata: parziale anche se altre sono ancora in lavorazione.
            return StatoCommessa::ConsegnataParziale;
        }

        if ($tutteMin3) {
            return StatoCommessa::CompletataInternamente;
        }

        $almenoUnaAvviata = max($stati) >= 2;

        return $almenoUnaAvviata
            ? StatoCommessa::InCorso
            : StatoCommessa::Aperta;
    }

    /**
     * True se tutte le fasi della commessa sono terminate (>= 3).
     *
     * @param Collection<int, object> $fasi
     */
    public function eCompletata(Collection $fasi): bool
    {
        if ($fasi->isEmpty()) {
            return false;
        }

        return $fasi->every(fn ($f) => $this->statoIntero($f) >= 3);
    }

    /**
     * Normalizza lo stato fase a int. In DB capita di trovare stringhe ("pausa")
     * — quelle non sono "terminate", quindi le mappiamo a 2 (avviato).
     */
    private function statoIntero(object $fase): int
    {
        $raw = $fase->stato ?? 0;

        if (is_int($raw)) {
            return $raw;
        }

        if (is_numeric($raw)) {
            return (int) $raw;
        }

        // Stringa non numerica (es. 'pausa') = fase ancora in corso.
        return 2;
    }

    /**
     * @param array<int> $stati
     */
    private function tuttiUguali(array $stati, int $target): bool
    {
        foreach ($stati as $s) {
            if ($s !== $target) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array<int> $stati
     */
    private function tuttiAlmeno(array $stati, int $min): bool
    {
        foreach ($stati as $s) {
            if ($s < $min) {
                return false;
            }
        }
        return true;
    }
}
