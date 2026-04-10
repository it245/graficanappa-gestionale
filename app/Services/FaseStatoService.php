<?php

namespace App\Services;

use App\Models\OrdineFase;
use App\Models\Ordine;

class FaseStatoService
{
    /**
     * Ricalcola gli stati delle fasi di una commessa.
     * 0 = caricato, 1 = pronto, 2 = avviato, 3 = terminato, 4 = consegnato, 5 = esterno
     * Una fase passa a 1 (pronto) solo se tutte le fasi con priorità inferiore sono a 3 (terminato).
     */
    public static function ricalcolaStati($ordineId)
    {
        $ordine = Ordine::find($ordineId);
        if (!$ordine) return;

        $fasi = OrdineFase::where('ordine_id', $ordineId)->orderBy('priorita')->orderBy('id')->get();
        if ($fasi->isEmpty()) return;

        // Predecessori: cerca in TUTTA la commessa (non solo stesso ordine)
        // Commesse multi-ordine hanno fasi su ordini diversi (es. copertina + interno)
        $tutteFasiCommessa = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $ordine->commessa))
            ->get();

        $flussoRaw = config('fasi_priorita', []);
        // Lookup case-insensitive: chiavi uppercase
        $flusso = array_change_key_case($flussoRaw, CASE_UPPER);

        foreach ($fasi as $fase) {
            // Se già avviato (2), terminato (3), consegnato (4) o esterno (5), non toccare
            if (is_numeric($fase->stato) && (int) $fase->stato >= 2) continue;

            // STAMPA XL (offset) non viene MAI promossa automaticamente a stato 1
            $faseUpper = strtoupper($fase->fase ?? '');
            if (str_starts_with($faseUpper, 'STAMPAXL106') || str_starts_with($faseUpper, 'STAMPA XL') || str_starts_with($faseUpper, 'STAMPA')) continue;

            // Predecessori basati sul flusso produttivo reale (config)
            // Cerca in tutta la commessa per trovare fasi su altri ordini
            $mioOrdine = $flusso[strtoupper($fase->fase)] ?? 500;
            $fasiPrecedenti = $tutteFasiCommessa->filter(fn($f) =>
                $f->id !== $fase->id && ($flusso[strtoupper($f->fase)] ?? 500) < $mioOrdine
            );

            if ($fasiPrecedenti->isEmpty()) {
                // Nessuna fase precedente: non toccare lo stato (rispetta modifiche manuali)
            } else {
                // Fase precedente in pausa (stato stringa non numerica) = NON terminata
                // stato 5 (esterno) NON conta come terminata — la fase è ancora dal fornitore
                $tuttTerminate = $fasiPrecedenti->every(fn($f) => is_numeric($f->stato) && (int) $f->stato >= 3 && (int) $f->stato != 5);
                $qualcunaInPausa = $fasiPrecedenti->contains(fn($f) => !is_numeric($f->stato));

                if ($tuttTerminate && !$qualcunaInPausa && $fase->stato == 0) {
                    $fase->stato = 1;
                    $fase->save();
                } elseif ((!$tuttTerminate || $qualcunaInPausa) && $fase->stato == 1) {
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

        // Per fasi stampa offset: non auto-terminare basandosi solo sui fogli
        // perché commesse con montaggi multipli possono avere fogli > qta_fase
        // ma non aver finito tutti i montaggi. Lasciare che Prinect (stato COMPLETED) termini.
        $isStampaOffset = str_starts_with($fase->fase ?? '', 'STAMPAXL106') || str_starts_with($fase->fase ?? '', 'STAMPA XL') || ($fase->fase ?? '') === 'STAMPA';
        if ($isStampaOffset && $fase->fogli_buoni > 0) {
            $completata = false; // Prinect gestisce la terminazione della stampa
        }

        if ($completata && $fase->stato < 3 && (int) $fase->stato !== 5) {
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
                ->whereRaw("stato REGEXP '^[0-9]+$' AND (stato < 4 OR stato = 5)")
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

        $fasi = OrdineFase::whereIn('ordine_id', $ordineIds)->orderBy('priorita')->orderBy('id')->get();
        if ($fasi->isEmpty()) return;

        // Usa la config fasi_priorita per determinare il flusso produttivo reale
        $flussoRaw = config('fasi_priorita', []);
        $flusso = array_change_key_case($flussoRaw, CASE_UPPER);

        foreach ($fasi as $fase) {
            if (is_numeric($fase->stato) && (int) $fase->stato >= 2) continue;

            // STAMPA XL (offset) non viene MAI promossa automaticamente a stato 1
            $faseUpper = strtoupper($fase->fase ?? '');
            if (str_starts_with($faseUpper, 'STAMPAXL106') || str_starts_with($faseUpper, 'STAMPA XL') || str_starts_with($faseUpper, 'STAMPA')) continue;

            // Ordine nel flusso produttivo reale (dalla config)
            $mioOrdine = $flusso[strtoupper($fase->fase)] ?? 500;

            // Predecessori: fasi con ordine di flusso inferiore (vengono prima nel ciclo)
            $fasiPrecedenti = $fasi->filter(fn($f) =>
                $f->id !== $fase->id && ($flusso[strtoupper($f->fase)] ?? 500) < $mioOrdine
            );

            if ($fasiPrecedenti->isEmpty()) {
                // Nessuna fase precedente: non toccare lo stato (rispetta modifiche manuali)
            } else {
                // stato 5 (esterno) NON conta come terminata
                $tuttTerminate = $fasiPrecedenti->every(fn($f) => is_numeric($f->stato) && (int) $f->stato >= 3 && (int) $f->stato != 5);
                $qualcunaInPausa = $fasiPrecedenti->contains(fn($f) => !is_numeric($f->stato));

                if ($tuttTerminate && !$qualcunaInPausa && $fase->stato == 0) {
                    $fase->stato = 1;
                    $fase->save();
                } elseif ((!$tuttTerminate || $qualcunaInPausa) && $fase->stato == 1) {
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
