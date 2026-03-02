<?php

namespace App\Services;

use App\Models\OrdineFase;
use App\Models\Ordine;

class FaseStatoService
{
    /**
     * Ricalcola gli stati delle fasi di una commessa.
     * 0 = caricato, 1 = pronto, 2 = avviato, 3 = terminato, 4 = consegnato
     * Una fase passa a 1 (pronto) solo se tutte le fasi con priorità inferiore sono a 3 (terminato).
     */
    public static function ricalcolaStati($ordineId)
    {
        $fasi = OrdineFase::where('ordine_id', $ordineId)->orderBy('priorita')->orderBy('id')->get();

        if ($fasi->isEmpty()) return;

        foreach ($fasi as $fase) {
            // Se già avviato (2) o terminato (3), non toccare
            if ($fase->stato >= 2) continue;

            // Cerca tutte le fasi con priorità inferiore (= prima nel flusso produttivo)
            $fasiPrecedenti = $fasi->filter(fn($f) =>
                $f->id !== $fase->id && ($f->priorita ?? 0) < ($fase->priorita ?? 0)
            );

            if ($fasiPrecedenti->isEmpty()) {
                // Nessuna fase precedente → pronto (1)
                if ($fase->stato == 0) {
                    $fase->stato = 1;
                    $fase->save();
                }
            } else {
                // Tutte le precedenti devono essere terminate (3)
                $tuttTerminate = $fasiPrecedenti->every(fn($f) => $f->stato >= 3);
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
        $fase = OrdineFase::with('ordine')->find($faseId);
        if (!$fase) return;

        $qtaProd = $fase->qta_prod ?? 0;
        $fogliProdotti = max($fase->fogli_buoni ?? 0, $qtaProd);
        $qtaFase = $fase->qta_fase ?? 0;
        $qtaCarta = $fase->ordine->qta_carta ?? 0;

        $completata = false;

        // Check 1: qta_prod >= qta_fase
        if ($qtaProd > 0 && $qtaFase > 0 && $qtaProd >= $qtaFase) {
            $completata = true;
        }

        // Check 2: qta_prod >= qta_carta (quando qta_fase è 0)
        if (!$completata && $qtaProd > 0 && $qtaFase == 0 && $qtaCarta > 0 && $qtaProd >= $qtaCarta) {
            $completata = true;
        }

        // Check 3: fogli prodotti + scarti_previsti >= qta_carta
        if (!$completata && $fogliProdotti > 0 && ($fase->scarti_previsti ?? 0) > 0) {
            if ($qtaCarta > 0 && ($fogliProdotti + $fase->scarti_previsti) >= $qtaCarta) {
                $completata = true;
            }
        }

        if ($completata && $fase->stato < 3) {
            $fase->stato = 3;
            $fase->data_fine = now()->format('Y-m-d H:i:s');
            $fase->save();
            self::ricalcolaStati($fase->ordine_id);
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
