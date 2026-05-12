<?php

declare(strict_types=1);

namespace App\Modules\Scheduling\Services;

use App\Models\OrdineFase;
use App\Modules\Scheduling\Rules\SequenzaFasiRule;

/**
 * Propaga la terminazione di una fase ai suoi successori logici
 * sulla stessa commessa: le fasi con tutti i predecessori >= 3
 * (terminate) vengono marcate PRONTE (stato 1) e disponibili.
 *
 * Non rimpiazza l'event listener su {@see PhaseCompleted}: incapsula
 * le regole di transizione perché siano riutilizzabili e testabili.
 */
final class PropagazioneService
{
    public function propagaFineFase(OrdineFase $faseTerminata): void
    {
        $ordine = $faseTerminata->ordine ?? null;
        if ($ordine === null) {
            return;
        }
        $commessa = $ordine->commessa ?? null;
        if (! $commessa) {
            return;
        }

        // Una commessa puo' avere PIU' ordini (multi-articolo). La
        // propagazione deve coprire TUTTE le fasi della commessa, non
        // solo quelle dello stesso ordine_id (bug pre-fix: fasi su ordini
        // distinti della stessa commessa restavano disponibile=false).
        $altreFasi = OrdineFase::with('ordine')
            ->whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
            ->whereNull('deleted_at')
            ->get();

        foreach ($altreFasi as $altra) {
            if ($altra->id === $faseTerminata->id) {
                continue;
            }

            $stato = (int) $altra->stato;
            if ($stato >= 2) {
                continue;
            }

            if (! SequenzaFasiRule::puoIniziare($altra)) {
                continue;
            }

            $altra->fill([
                'stato'           => 1,
                'disponibile'     => true,
                'disponibile_m37' => true,
            ])->save();
        }
    }
}
