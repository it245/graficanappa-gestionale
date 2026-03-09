<?php

namespace App\Services;

use App\Models\OrdineFase;
use App\Models\Ordine;
use App\Helpers\DescrizioneParser;
use Carbon\Carbon;

/**
 * Mossa 37 — Sistema priorità a 4 livelli
 *
 * Livello 1: Disponibilità fisica (predecessori completati)
 * Livello 2: Urgenza reale (giorni_rimasti - giorni_lavoro_residuo)
 * Livello 3: Affinità attrezzaggio / batching (batch_key)
 * Livello 4: Posizione nel ciclo (sequenza)
 *
 * Formula: priorita = (fascia × 10000) + (urgenza_reale × 100) + sequenza
 * Fasce: 1=CRITICA (<0), 2=URGENTE (0-5), 3=NORMALE (5-15), 4=PIANIFICABILE (>15)
 */
class PriorityService
{
    // Soglie fasce urgenza (in giorni)
    const FASCIA_CRITICA      = 1; // urgenza_reale < 0
    const FASCIA_URGENTE      = 2; // 0 <= urgenza_reale < 5
    const FASCIA_NORMALE      = 3; // 5 <= urgenza_reale < 15
    const FASCIA_PIANIFICABILE = 4; // urgenza_reale >= 15

    /**
     * Ricalcola tutti i campi Mossa 37 per le fasi di una commessa.
     * Chiamato dopo sync Onda, completamento fase, o manualmente.
     */
    public static function ricalcolaCommessa(string $commessa): void
    {
        $ordini = Ordine::where('commessa', $commessa)->with('fasi')->get();
        if ($ordini->isEmpty()) return;

        // Raccogli tutte le fasi attive (non terminate/consegnate)
        $tutteFasi = $ordini->flatMap->fasi;

        foreach ($tutteFasi as $fase) {
            if ($fase->stato >= 3) continue; // terminata o consegnata, skip

            $ordine = $ordini->firstWhere('id', $fase->ordine_id);

            self::calcolaSequenza($fase);
            self::calcolaDisponibile($fase, $tutteFasi);
            self::calcolaOre($fase, $ordine);
            self::calcolaGiorniLavoroResiduo($fase, $ordine, $tutteFasi);
            self::calcolaUrgenzaReale($fase, $ordine);
            self::calcolaFasciaUrgenza($fase);
            self::calcolaBatchKey($fase, $ordine);
            self::calcolaPriorita($fase);

            $fase->save();
        }
    }

    /**
     * Livello 4: Assegna la sequenza nel ciclo produttivo dalla config.
     */
    public static function calcolaSequenza(OrdineFase $fase): void
    {
        $sequenze = config('sequenza_fasi', []);
        $fase->sequenza = $sequenze[$fase->fase] ?? 500;
    }

    /**
     * Livello 1: Disponibilità fisica.
     * Una fase è disponibile se tutti i predecessori (sequenza inferiore)
     * nella stessa commessa sono completati (stato >= 3).
     */
    public static function calcolaDisponibile(OrdineFase $fase, $tutteFasi): void
    {
        $miaSequenza = $fase->sequenza;

        // Predecessori: fasi della stessa commessa con sequenza strettamente inferiore
        // (ordine_id diverso ma stessa commessa — tutteFasi contiene già solo la commessa)
        $predecessori = $tutteFasi->filter(fn($f) =>
            $f->id !== $fase->id && ($f->sequenza ?? 500) < $miaSequenza
        );

        if ($predecessori->isEmpty()) {
            // Nessun predecessore → prima fase del ciclo, sempre disponibile
            $fase->disponibile = true;
        } else {
            $fase->disponibile = $predecessori->every(fn($f) => $f->stato >= 3);
        }
    }

    /**
     * Calcola ore previste per la fase usando config/fasi_ore.php
     * Formula: ore = avviamento + (qta_carta / copieh)
     */
    public static function calcolaOre(OrdineFase $fase, Ordine $ordine): void
    {
        $fasiOre = config('fasi_ore', []);
        $info = $fasiOre[$fase->fase] ?? null;

        if (!$info) {
            $fase->ore = 0;
            return;
        }

        $avviamento = $info['avviamento'] ?? 0;
        $copieh = $info['copieh'] ?? 1;
        $qtaCarta = $ordine->qta_carta ?? 0;

        $fase->ore = round($avviamento + ($qtaCarta / max($copieh, 1)), 2);
    }

    /**
     * Calcola i giorni di lavoro residuo: somma ore di tutte le fasi
     * non completate con sequenza >= alla fase corrente, diviso 24.
     */
    public static function calcolaGiorniLavoroResiduo(OrdineFase $fase, Ordine $ordine, $tutteFasi): void
    {
        $fasiOre = config('fasi_ore', []);
        $miaSequenza = $fase->sequenza;
        $qtaCarta = $ordine->qta_carta ?? 0;

        $oreResiduo = 0;

        // Somma le ore di tutte le fasi non completate dalla corrente in poi
        $fasiResiduo = $tutteFasi->filter(fn($f) =>
            $f->stato < 3 && ($f->sequenza ?? 500) >= $miaSequenza
        );

        foreach ($fasiResiduo as $f) {
            $info = $fasiOre[$f->fase] ?? null;
            if ($info) {
                $avv = $info['avviamento'] ?? 0;
                $cph = max($info['copieh'] ?? 1, 1);
                $oreResiduo += $avv + ($qtaCarta / $cph);
            }
        }

        $fase->giorni_lavoro_residuo = round($oreResiduo / 24, 2);
    }

    /**
     * Livello 2: Urgenza reale.
     * urgenza_reale = giorni_rimasti - giorni_lavoro_residuo
     * Negativo = in ritardo, positivo = margine.
     */
    public static function calcolaUrgenzaReale(OrdineFase $fase, Ordine $ordine): void
    {
        $dataConsegna = $ordine->data_prevista_consegna;
        if (!$dataConsegna) {
            $fase->urgenza_reale = 99; // nessuna scadenza → bassa urgenza
            return;
        }

        $giorniRimasti = Carbon::today()->diffInDays(Carbon::parse($dataConsegna), false);
        $fase->urgenza_reale = round($giorniRimasti - ($fase->giorni_lavoro_residuo ?? 0), 2);
    }

    /**
     * Livello 2b: Fascia di urgenza.
     * 1=CRITICA, 2=URGENTE, 3=NORMALE, 4=PIANIFICABILE
     */
    public static function calcolaFasciaUrgenza(OrdineFase $fase): void
    {
        $u = $fase->urgenza_reale ?? 99;

        if ($u < 0) {
            $fase->fascia_urgenza = self::FASCIA_CRITICA;
        } elseif ($u < 5) {
            $fase->fascia_urgenza = self::FASCIA_URGENTE;
        } elseif ($u < 15) {
            $fase->fascia_urgenza = self::FASCIA_NORMALE;
        } else {
            $fase->fascia_urgenza = self::FASCIA_PIANIFICABILE;
        }
    }

    /**
     * Livello 3: Batch key per affinità attrezzaggio.
     * - Fustellatura: raggruppa per codice fustella (FS)
     * - Piega-incolla: raggruppa per tipo macchina (PI01/PI02/PI03)
     * - Stampa a caldo: raggruppa per tipo (CALDO-JOH, CALDO-BR, ecc.)
     * - Default: nome fase
     */
    public static function calcolaBatchKey(OrdineFase $fase, Ordine $ordine): void
    {
        $seq = $fase->sequenza ?? 500;

        // Fustellatura (seq 40): raggruppa per codice fustella FS
        if ($seq == 40) {
            $fs = DescrizioneParser::parseFustella(
                $ordine->descrizione ?? '',
                $ordine->cliente_nome ?? ''
            );
            $fase->batch_key = $fs ? 'FUST-' . $fs : 'FUST-' . $fase->fase;
            return;
        }

        // Piega-incolla (seq 110): raggruppa per macchina
        if ($seq == 110) {
            $fase->batch_key = 'PI-' . $fase->fase;
            return;
        }

        // Stampa a caldo (seq 30): raggruppa per tipo
        if ($seq == 30) {
            $fase->batch_key = 'CALDO-' . $fase->fase;
            return;
        }

        // Rilievi BOBST (seq 39) vs fustellatura BOBST
        if ($seq == 39) {
            $fase->batch_key = 'BOBST-RILIEVI';
            return;
        }

        // Default: raggruppa per nome fase
        $fase->batch_key = $fase->fase;
    }

    /**
     * Formula finale di priorità.
     * priorita = (fascia × 10000) + (urgenza_reale_normalizzata × 100) + sequenza
     *
     * - fascia pesa di più (ordinamento primario)
     * - urgenza_reale ordina dentro la fascia
     * - sequenza disambigua a parità di urgenza
     *
     * Più basso = più urgente.
     */
    public static function calcolaPriorita(OrdineFase $fase): void
    {
        // Se ha priorità manuale, non sovrascrivere
        if ($fase->priorita_manuale) return;

        $fascia = $fase->fascia_urgenza ?? self::FASCIA_PIANIFICABILE;

        // Normalizza urgenza_reale in un range 0-99 per il peso dentro la fascia
        // clamp tra -50 e +50, poi shift a 0-100
        $urgenza = $fase->urgenza_reale ?? 50;
        $urgenzaNorm = max(0, min(99, $urgenza + 50));

        $sequenza = min($fase->sequenza ?? 500, 999);

        $fase->priorita = ($fascia * 10000) + ($urgenzaNorm * 100) + $sequenza;
    }

    /**
     * Ricalcola tutte le commesse attive.
     * Da chiamare dopo sync Onda o come batch notturno.
     */
    public static function ricalcolaTutti(): void
    {
        $commesse = Ordine::whereHas('fasi', fn($q) => $q->where('stato', '<', 3))
            ->distinct()
            ->pluck('commessa');

        foreach ($commesse as $commessa) {
            self::ricalcolaCommessa($commessa);
        }
    }

    /**
     * Propaga disponibilità dopo completamento di una fase.
     * Ricalcola solo le fasi successive nella stessa commessa.
     */
    public static function propagaDisponibilita(OrdineFase $faseCompletata): void
    {
        $ordine = $faseCompletata->ordine;
        if (!$ordine || !$ordine->commessa) return;

        self::ricalcolaCommessa($ordine->commessa);
    }

    /**
     * Restituisce etichetta leggibile della fascia urgenza.
     */
    public static function etichettaFascia(?int $fascia): string
    {
        return match ($fascia) {
            self::FASCIA_CRITICA       => 'CRITICA',
            self::FASCIA_URGENTE       => 'URGENTE',
            self::FASCIA_NORMALE       => 'NORMALE',
            self::FASCIA_PIANIFICABILE => 'PIANIFICABILE',
            default                    => '-',
        };
    }

    /**
     * Restituisce classe CSS Bootstrap per la fascia urgenza.
     */
    public static function classeFascia(?int $fascia): string
    {
        return match ($fascia) {
            self::FASCIA_CRITICA       => 'danger',
            self::FASCIA_URGENTE       => 'warning',
            self::FASCIA_NORMALE       => 'info',
            self::FASCIA_PIANIFICABILE => 'secondary',
            default                    => 'light',
        };
    }
}
