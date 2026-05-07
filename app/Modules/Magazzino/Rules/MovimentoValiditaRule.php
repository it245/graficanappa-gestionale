<?php

declare(strict_types=1);

namespace App\Modules\Magazzino\Rules;

use App\Models\MagazzinoArticolo;
use App\Modules\Magazzino\Enums\TipoMovimento;

/**
 * Regola di validazione di un movimento di magazzino prima della scrittura.
 *
 * Restituisce un array di errori chiave => messaggio. Array vuoto = OK.
 *
 * Casistiche coperte:
 *  - Articolo mancante o non attivo
 *  - Quantità zero o segno errato per il tipo
 *  - SCARICO con quantità superiore alla giacenza disponibile
 *  - RESO con qta > carichi totali (anti-frode di base)
 *
 * @example
 *  $errori = (new MovimentoValiditaRule())->validate(
 *      TipoMovimento::Scarico, 1500.0, $articolo
 *  );
 *  if ($errori !== []) { throw ValidationException::withMessages($errori); }
 */
final class MovimentoValiditaRule
{
    /**
     * @return array<string,string>
     */
    public function validate(TipoMovimento $tipo, float $qta, ?MagazzinoArticolo $articolo): array
    {
        $errori = [];

        if ($articolo === null) {
            $errori['articolo'] = 'Articolo non trovato.';
            return $errori; // senza articolo non si valida oltre
        }

        if (! (bool) ($articolo->attivo ?? true)) {
            $errori['articolo'] = "Articolo {$articolo->codice} non attivo.";
        }

        if (abs($qta) < 1e-9) {
            $errori['quantita'] = 'La quantità non può essere zero.';
        }

        if ($tipo->richiedeQuantitaPositiva() && $qta <= 0) {
            $errori['quantita'] = "Tipo {$tipo->value} richiede quantità positiva.";
        }

        $giacenza = (int) $articolo->giacenze()->sum('quantita');

        if ($tipo === TipoMovimento::Scarico && $qta > $giacenza) {
            $errori['quantita'] = sprintf(
                'Scarico %s richiesto: %s, disponibile: %d.',
                $articolo->codice,
                rtrim(rtrim(number_format($qta, 3, '.', ''), '0'), '.'),
                $giacenza,
            );
        }

        if ($tipo === TipoMovimento::Reso) {
            $totaleCarichi = (int) $articolo->movimenti()
                ->where('tipo', TipoMovimento::Carico->value)
                ->sum('quantita');
            if ($qta > $totaleCarichi) {
                $errori['quantita'] = "Reso {$qta} > carichi totali {$totaleCarichi} per {$articolo->codice}.";
            }
        }

        if ($tipo === TipoMovimento::Rettifica && $qta < 0 && abs($qta) > $giacenza) {
            $errori['quantita'] = "Rettifica negativa porterebbe giacenza sotto zero ({$giacenza} + {$qta}).";
        }

        return $errori;
    }
}
