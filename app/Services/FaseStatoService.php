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
            // Escludi fasi con priorità manuale (ordine visualizzazione, non flusso)
            $fasiPrecedenti = $fasi->filter(fn($f) =>
                $f->id !== $fase->id
                && !$f->priorita_manuale
                && ($f->priorita ?? 0) < ($fase->priorita ?? 0)
            );

            if ($fasiPrecedenti->isEmpty()) {
                // Nessuna fase precedente → resta a 0 (non promuovere automaticamente)
                // Se era già a 1 (promossa prima del fix), riportala a 0
                if ($fase->stato == 1) {
                    $fase->stato = 0;
                    $fase->save();
                }
            } else {
                $tuttTerminate = $fasiPrecedenti->every(fn($f) => $f->stato >= 3);
                if ($tuttTerminate && $fase->stato == 0) {
                    // Tutte le precedenti terminate → pronto (1)
                    $fase->stato = 1;
                    $fase->save();
                } elseif (!$tuttTerminate && $fase->stato == 1) {
                    // Predecessori non tutte terminate → torna a caricato (0)
                    $fase->stato = 0;
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
        $qtaRichiesta = $fase->ordine->qta_richiesta ?? 0;
        $qtaCarta = $fase->ordine->qta_carta ?? 0;

        $completata = false;

        // Check 1: qta_prod >= qta_richiesta (pezzi prodotti coprono l'ordine)
        if ($qtaProd > 0 && $qtaRichiesta > 0 && $qtaProd >= $qtaRichiesta) {
            $completata = true;
        }

        // Check 2: qta_prod >= qta_fase (fogli prodotti coprono i fogli richiesti inclusi scarti)
        if (!$completata && $qtaProd > 0 && $qtaFase > 0 && $qtaProd >= $qtaFase) {
            $completata = true;
        }

        // Check 3: qta_prod >= qta_carta (quando qta_fase è 0)
        if (!$completata && $qtaProd > 0 && $qtaFase == 0 && $qtaCarta > 0 && $qtaProd >= $qtaCarta) {
            $completata = true;
        }

        // Check 4: fogli prodotti + scarti_previsti >= qta_carta
        if (!$completata && $fogliProdotti > 0 && ($fase->scarti_previsti ?? 0) > 0) {
            if ($qtaCarta > 0 && ($fogliProdotti + $fase->scarti_previsti) >= $qtaCarta) {
                $completata = true;
            }
        }

        if ($completata && $fase->stato < 3) {
            $fase->stato = 3;
            $fase->data_fine = now()->format('Y-m-d H:i:s');
            $fase->save();
            self::ricalcolaCommessa($fase->ordine->commessa ?? null);
        }
    }

    /**
     * Se BRT della commessa è a stato 4 (consegnato), tutte le fasi della commessa vanno a 4.
     */
    public static function propagaConsegnato($commessa)
    {
        $ordineIds = Ordine::where('commessa', $commessa)->pluck('id');
        if ($ordineIds->isEmpty()) return;

        $brtConsegnato = OrdineFase::whereIn('ordine_id', $ordineIds)
            ->whereIn('fase', ['BRT1', 'brt1', 'BRT'])
            ->where('stato', 4)
            ->exists();

        if ($brtConsegnato) {
            OrdineFase::whereIn('ordine_id', $ordineIds)
                ->where('stato', '<', 4)
                ->update(['stato' => 4, 'data_fine' => now()->format('Y-m-d H:i:s')]);
        }
    }

    /**
     * Ricalcola gli stati di tutte le fasi di una commessa (tutti gli ordini).
     */
    public static function ricalcolaCommessa($commessa)
    {
        if (!$commessa) return;

        $ordineIds = Ordine::where('commessa', $commessa)->pluck('id');
        if ($ordineIds->isEmpty()) return;

        // Prendi TUTTE le fasi della commessa, ordinate per priorità
        $fasi = OrdineFase::whereIn('ordine_id', $ordineIds)->orderBy('priorita')->orderBy('id')->get();
        if ($fasi->isEmpty()) return;

        foreach ($fasi as $fase) {
            if ($fase->stato >= 2) continue;

            // Predecessori: priorità inferiore, ma escludi fasi con priorità manuale
            // (la priorità manuale è solo per l'ordine di visualizzazione, non per il flusso)
            $fasiPrecedenti = $fasi->filter(fn($f) =>
                $f->id !== $fase->id
                && !$f->priorita_manuale
                && ($f->priorita ?? 0) < ($fase->priorita ?? 0)
            );

            if ($fasiPrecedenti->isEmpty()) {
                if ($fase->stato == 1) {
                    $fase->stato = 0;
                    $fase->save();
                }
            } else {
                $tuttTerminate = $fasiPrecedenti->every(fn($f) => $f->stato >= 3);
                if ($tuttTerminate && $fase->stato == 0) {
                    $fase->stato = 1;
                    $fase->save();
                } elseif (!$tuttTerminate && $fase->stato == 1) {
                    $fase->stato = 0;
                    $fase->save();
                }
            }
        }
    }

    /**
     * Dopo import: ricalcola tutti gli stati di tutte le commesse
     */
    public static function ricalcolaTutti()
    {
        $commesse = Ordine::distinct()->pluck('commessa');
        foreach ($commesse as $commessa) {
            self::ricalcolaCommessa($commessa);
            self::propagaConsegnato($commessa);
        }
    }
}
