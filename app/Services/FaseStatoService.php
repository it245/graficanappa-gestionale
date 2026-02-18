<?php

namespace App\Services;

use App\Models\OrdineFase;
use App\Models\Ordine;

class FaseStatoService
{
    /**
     * Ricalcola gli stati delle fasi di una commessa.
     * 0 = caricato, 1 = pronto, 2 = avviato, 3 = terminato
     * Una fase passa a 1 (pronto) solo se tutte le fasi con priorità inferiore sono a 3 (terminato).
     */
    public static function ricalcolaStati($ordineId)
    {
        $fasi = OrdineFase::where('ordine_id', $ordineId)->orderBy('id')->get();

        if ($fasi->isEmpty()) return;

        foreach ($fasi as $fase) {
            // Se già avviato (2) o terminato (3), non toccare
            if ($fase->stato >= 2) continue;

            // Cerca tutte le fasi precedenti (id minore) non ancora terminate
            $fasiPrecedenti = $fasi->filter(fn($f) => $f->id < $fase->id && $f->id !== $fase->id);

            if ($fasiPrecedenti->isEmpty()) {
                // Nessuna fase precedente → pronto (1)
                if ($fase->stato == 0) {
                    $fase->stato = 1;
                    $fase->save();
                }
            } else {
                // Tutte le precedenti devono essere terminate (3)
                $tuttTerminate = $fasiPrecedenti->every(fn($f) => $f->stato == 3);
                if ($tuttTerminate && $fase->stato == 0) {
                    $fase->stato = 1;
                    $fase->save();
                }
            }
        }
    }

    /**
     * Controlla se qta_prod >= qta_fase → stato = 3 (terminato)
     */
    public static function controllaCompletamento($faseId)
    {
        $fase = OrdineFase::find($faseId);
        if (!$fase) return;

        if ($fase->qta_prod > 0 && $fase->qta_fase > 0 && $fase->qta_prod >= $fase->qta_fase) {
            if ($fase->stato < 3) {
                $fase->stato = 3;
                $fase->data_fine = now()->format('d/m/Y H:i:s');
                $fase->save();
                // Ricalcola stati delle fasi successive
                self::ricalcolaStati($fase->ordine_id);
            }
        }
    }

    /**
     * Dopo import: ricalcola tutti gli stati di tutte le commesse
     */
    public static function ricalcolaTutti()
    {
        $ordineIds = OrdineFase::distinct()->pluck('ordine_id');
        foreach ($ordineIds as $ordineId) {
            self::ricalcolaStati($ordineId);
        }
    }
}
