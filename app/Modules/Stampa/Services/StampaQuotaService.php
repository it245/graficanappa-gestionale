<?php

declare(strict_types=1);

namespace App\Modules\Stampa\Services;

use App\Models\OrdineFase;
use InvalidArgumentException;

/**
 * Service di dominio per la gestione della quota stampa di una fase.
 *
 * Centralizza il calcolo "quanto manca da stampare" e l'aggiornamento
 * sicuro di `qta_prod` su `ordine_fasi`, in modo che i controller
 * (PrinectController, FieryController, dashboard operatore) non
 * duplichino la logica.
 *
 * Sorgenti di verità:
 *  - qta_fase   → tiratura richiesta (target)
 *  - qta_prod   → fogli buoni già prodotti
 *  - scarti     → fogli di scarto (non scalano la quota residua)
 */
final class StampaQuotaService
{
    /**
     * Restituisce le copie ancora da stampare per la fase.
     * Mai negativo: se qta_prod ha sforato il target, restituisce 0.
     */
    public function quotaResidua(OrdineFase $fase): int
    {
        $target  = (int) ($fase->qta_fase ?? 0);
        $fatte   = (int) ($fase->qta_prod ?? 0);

        return max(0, $target - $fatte);
    }

    /**
     * Imposta `qta_prod` al valore assoluto $nuoveCopie e persiste la fase.
     *
     * @param  OrdineFase  $fase
     * @param  int         $nuoveCopie  Totale copie buone (assoluto, non delta).
     * @throws InvalidArgumentException se $nuoveCopie < 0.
     */
    public function aggiornaQtaProd(OrdineFase $fase, int $nuoveCopie): void
    {
        if ($nuoveCopie < 0) {
            throw new InvalidArgumentException('qta_prod non può essere negativo.');
        }

        $fase->qta_prod = $nuoveCopie;
        $fase->save();
    }

    /**
     * Incrementa `qta_prod` di $delta copie buone e persiste la fase.
     * Comodo per i job sync che ricevono delta incrementali da API.
     */
    public function incrementaQtaProd(OrdineFase $fase, int $delta): void
    {
        if ($delta < 0) {
            throw new InvalidArgumentException('Delta non può essere negativo (usa aggiornaQtaProd per overwrite).');
        }

        $attuale = (int) ($fase->qta_prod ?? 0);
        $this->aggiornaQtaProd($fase, $attuale + $delta);
    }
}
