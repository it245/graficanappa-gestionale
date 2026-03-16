<?php

namespace App\Listeners;

use App\Events\PhaseCompleted;
use App\Models\OrdineFase;
use App\Models\Ordine;
use Carbon\Carbon;

/**
 * Mossa 37 — Strato 1: propagazione istantanea (<100ms)
 *
 * Quando un operatore completa una fase:
 * 1. Trova tutti i successori (stessa commessa, sequenza maggiore)
 * 2. Per ogni successore, controlla se TUTTI i predecessori sono completati/in corso
 * 3. Se sì → disponibile_m37 = true
 * 4. Ricalcola urgenza_reale, fascia_urgenza, priorita_m37
 */
class PropagateAvailability
{
    public function handle(PhaseCompleted $event): void
    {
        $fase = $event->fase;
        $fase->loadMissing('ordine');
        $ordine = $fase->ordine;
        if (!$ordine) return;

        $commessa = $ordine->commessa;
        $seqMap = config('sequenza_fasi');
        $faseSeq = $seqMap[trim($fase->fase)] ?? 999;

        // Trova tutte le fasi della stessa commessa
        $fasiCommessa = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
            ->whereNull('deleted_at')
            ->with('ordine')
            ->get();

        // Per ogni fase successore (sequenza maggiore)
        foreach ($fasiCommessa as $succ) {
            $succSeq = $seqMap[trim($succ->fase)] ?? 999;
            if ($succSeq <= $faseSeq) continue;
            if ($succ->stato >= 3) continue; // già terminata
            if ($succ->disponibile_m37) continue; // già disponibile

            // Controlla se TUTTI i predecessori (seq minore) sono completati o in corso
            $predNonCompletati = $fasiCommessa->filter(function ($pred) use ($succSeq, $seqMap) {
                $predSeq = $seqMap[trim($pred->fase)] ?? 999;
                if ($predSeq >= $succSeq) return false; // non è un predecessore
                if ($pred->stato >= 2) return false; // in corso (2) o terminato (3+) = ok
                if ($pred->disponibile_m37 && $pred->stato >= 1) return false; // pronto e in lavorazione
                return true; // predecessore non completato
            });

            if ($predNonCompletati->isEmpty()) {
                // Tutti i predecessori ok → sblocca il successore
                $succ->update(['disponibile_m37' => true]);
                $this->aggiornaPriorita($succ);
            }
        }
    }

    /**
     * Ricalcola urgenza_reale, fascia_urgenza, priorita_m37
     * Formula dalla sezione 8.3 del documento
     */
    protected function aggiornaPriorita(OrdineFase $fase): void
    {
        $ordine = $fase->ordine;
        if (!$ordine) return;

        $seqMap = config('sequenza_fasi');
        $sequenza = $seqMap[trim($fase->fase)] ?? 999;

        $consegna = $ordine->data_prevista_consegna
            ? Carbon::parse($ordine->data_prevista_consegna)
            : now()->addDays(30);

        $giorniRimasti = round($consegna->diffInDays(now(), false), 2);

        // Stima giorni lavoro residuo
        $cfg = config('macchine_scheduler');
        $faseNome = trim($fase->fase);
        $params = $cfg['parametri'][$faseNome] ?? null;
        $avv = $params[0] ?? $cfg['default_avviamento'];
        $copieOra = $params[1] ?? $cfg['default_copie_ora'];
        $qtaLavoro = $fase->qta_fase ?: ($ordine->qta_carta ?: ($ordine->qta_richiesta ?: 0));
        $ore = ($copieOra > 0 && $qtaLavoro > 0) ? $avv + ($qtaLavoro / $copieOra) : $avv;
        $giorniLavoroResiduo = $ore / 16; // 16h/giorno lavorativo

        $urgenzaReale = $giorniRimasti - $giorniLavoroResiduo;

        if ($urgenzaReale < 0) $fascia = 0;      // critica
        elseif ($urgenzaReale <= 5) $fascia = 1;  // urgente
        elseif ($urgenzaReale <= 15) $fascia = 2; // normale
        else $fascia = 3;                          // pianificabile

        $prioritaM37 = ($fascia * 10000) + ($urgenzaReale * 100) + $sequenza;

        // Batch key
        $macchine = $cfg['macchine'];
        $faseMac = [];
        foreach ($macchine as $mid => $mc) {
            foreach ($mc['fasi'] as $f) {
                $faseMac[$f] = $mid;
            }
        }
        $mac = $faseMac[$faseNome] ?? null;
        $batchKey = null;
        if ($mac) {
            $fs = null; // TODO: estrarre fustella se disponibile
            $batchKey = match ($mac) {
                'XL106' => 'STD',
                'BOBST', 'STEL', 'JOH' => $fs ?? 'NOFS_' . $ordine->commessa,
                'PLAST' => $faseNome,
                default => $fs ?? 'NOFS_' . $ordine->commessa,
            };
        }

        $fase->update([
            'urgenza_reale' => round($urgenzaReale, 2),
            'fascia_urgenza' => $fascia,
            'giorni_lavoro_residuo' => round($giorniLavoroResiduo, 2),
            'sequenza_m37' => $sequenza,
            'priorita_m37' => round($prioritaM37, 2),
            'batch_key' => $batchKey ? substr($batchKey, 0, 50) : null,
        ]);
    }
}
