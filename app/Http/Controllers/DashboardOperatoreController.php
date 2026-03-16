<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdineFase;
use App\Helpers\DescrizioneParser;
use Carbon\Carbon;

class DashboardOperatoreController extends Controller
{
    public function index(Request $request)
    {
        // Operatore loggato (da token per-tab o fallback sessione)
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();
        if (!$operatore) abort(403, 'Accesso negato');

        // Recupera gli ID dei reparti dell'operatore (molti-a-molti)
        $reparti = $operatore->reparti->pluck('id')->toArray();

        // Sicurezza: se nessun reparto
        if (empty($reparti)) {
            $reparti = [];
        }

        // Mappa fasi → ore avviamento e copieh (da config condiviso)
        $fasiInfo = config('fasi_ore');

        // Recupera le fasi visibili per i reparti dell'operatore
        $fasiVisibili = OrdineFase::where(function ($q) use ($reparti) {
                // Fasi attive (stato < 3)
                $q->where('stato', '<', 3);
                // + fasi stampa offset chiuse da meno di 1h (per inserire scarti reali)
                $q->orWhere(function ($q2) use ($reparti) {
                    $q2->where('stato', 3)
                        ->where('data_fine', '>=', Carbon::now()->subHour())
                        ->whereHas('faseCatalogo', function ($q3) {
                            $q3->whereHas('reparto', fn($r) => $r->where('nome', 'stampa offset'));
                        });
                });
            })
            ->where(fn($q) => $q->where('esterno', false)->orWhereNull('esterno'))
            ->whereHas('faseCatalogo', function ($q) use ($reparti) {
                $q->whereIn('reparto_id', $reparti);
            })
            ->with(['ordine', 'faseCatalogo.reparto', 'operatori'])
            ->get()
            ->map(function ($fase) use ($fasiInfo) {
                $qta_carta = $fase->ordine->qta_carta ?? 0;
                $infoFase = $fasiInfo[$fase->fase] ?? ['avviamento' => 0, 'copieh' => 0];
                $copieh = $infoFase['copieh'] ?: 1;

                // ore = avviamento + qtaCarta / copieh (pezzi da fare / pezzi all'ora)
                $fase->ore = round($infoFase['avviamento'] + ($qta_carta / $copieh), 2);

                // giorni rimasti = dataPrevConsegna - OGGI (negativo se scaduta)
                $giorni_rimasti = 0;
                if ($fase->ordine && $fase->ordine->data_prevista_consegna) {
                    $oggi = \Carbon\Carbon::today();
                    $consegna = \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna)->startOfDay();
                    $giorni_rimasti = ($consegna->timestamp - $oggi->timestamp) / 86400;
                }

                // Se priorità manuale (impostata da owner/Excel), non ricalcolare
                $currentPriorita = $fase->priorita ?? 0;
                if ($fase->priorita_manuale || ($currentPriorita <= -901 && $currentPriorita >= -999)) {
                    // Mantieni la priorità dal DB
                } else {
                    // Ordine fase dalla config
                    $fasePriorita = config('fasi_priorita')[$fase->fase] ?? 500;

                    // Priorità = giorni rimasti - ore/24 + ordineFase/10000
                    $fase->priorita = round($giorni_rimasti - ($fase->ore / 24) + ($fasePriorita / 10000), 2);
                }

                // Colori e Fustella dalla descrizione
                $desc = $fase->ordine->descrizione ?? '';
                $cliente = $fase->ordine->cliente_nome ?? '';
                $repNome = optional($fase->faseCatalogo)->reparto->nome ?? '';
                $fase->colori = DescrizioneParser::parseColori($desc, $cliente, $repNome);
                $notePre = $fase->ordine->note_prestampa ?? '';
                $fase->fustella_codice = DescrizioneParser::parseFustella($desc, $cliente, $notePre);

                // Fornitore esterno: estrai "Inviato a: XXX" dalla note
                $fase->fornitore_esterno = preg_match('/Inviato a:\s*(.+)/i', $fase->note ?? '', $m) ? trim($m[1]) : null;

                // Pulisci "Inviato a: ..." dalla note visualizzata
                $fase->note_pulita = preg_replace('/,?\s*Inviato a:\s*.+/i', '', $fase->note ?? '');
                $fase->note_pulita = trim($fase->note_pulita, ", \t\n\r") ?: null;

                return $fase;
            })
            ->sortBy(function ($fase) {
                // Priorità manuale sempre in cima
                $manuale = ($fase->priorita_manuale ?? false) ? -1000000 + ($fase->priorita ?? 0) : 0;
                if ($manuale < 0) return $manuale;
                // Mossa 37 — Fallback a 3 livelli (sezione 8.3 spec)
                // 1. Se sched_posizione esiste e piano < 2h → usa posizione scheduler
                if ($fase->sched_posizione && $fase->sched_calcolato_at
                    && \Carbon\Carbon::parse($fase->sched_calcolato_at)->diffInHours(now()) < 2) {
                    return $fase->sched_posizione;
                }
                // 2. Se fascia_urgenza calcolata → ordina per fascia → urgenza_reale → sequenza
                if ($fase->fascia_urgenza !== null) {
                    return ($fase->fascia_urgenza * 10000)
                         + (($fase->urgenza_reale ?? 999) * 100)
                         + ($fase->sequenza_m37 ?? 999);
                }
                // 3. Fallback: priorità vecchia del gestionale
                return $fase->priorita ?? 9999;
            });

        // Flag per colonne condizionali
        $nomiReparti = $operatore->reparti->pluck('nome')->map(fn($n) => strtolower($n))->toArray();
        $showColori = !empty(array_intersect($nomiReparti, ['stampa offset']));
        $showFustella = true;
        $showEsterno = !empty(array_intersect($nomiReparti, ['spedizione']));
        $isFustellaOperatore = !empty(array_intersect($nomiReparti, ['fustella piana', 'fustella cilindrica']));
        $showScarti = !empty(array_intersect($nomiReparti, ['stampa offset']));
        // Raggruppa fasi per reparto (per operatori multi-reparto)
        $repartiOperatore = $operatore->reparti->sortBy('nome');
        $fasiPerReparto = [];
        if ($repartiOperatore->count() > 1) {
            foreach ($repartiOperatore as $rep) {
                $fasiPerReparto[$rep->id] = [
                    'nome' => ucfirst($rep->nome),
                    'fasi' => $fasiVisibili->filter(function ($fase) use ($rep) {
                        return optional($fase->faseCatalogo)->reparto_id == $rep->id;
                    }),
                ];
            }
        }

        return view('operatore.dashboard', compact('fasiVisibili', 'operatore', 'fasiPerReparto', 'showColori', 'showFustella', 'showEsterno', 'isFustellaOperatore', 'showScarti'));
    }

    /**
     * Pagina Fustelle: mostra le fustelle da utilizzare nei prossimi 30 giorni
     * raggruppate per codice FS, con commesse associate.
     * Visibile solo agli operatori di fustella piana/cilindrica.
     */
    public function fustelle(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();
        if (!$operatore) abort(403, 'Accesso negato');

        // Reparti fustella: 5=fustella, 15=fustella piana, 16=fustella cilindrica
        $repartiFustella = [5, 15, 16];

        // Fasi nei reparti fustella con stato < 3 e consegna nei prossimi 30 giorni
        $fasi = OrdineFase::where('stato', '<', 3)
            ->where(fn($q) => $q->where('esterno', false)->orWhereNull('esterno'))
            ->whereHas('faseCatalogo', function ($q) use ($repartiFustella) {
                $q->whereIn('reparto_id', $repartiFustella);
            })
            ->whereHas('ordine', function ($q) {
                $q->where('data_prevista_consegna', '<=', Carbon::today()->addDays(30));
            })
            ->with(['ordine', 'faseCatalogo.reparto'])
            ->get();

        // Raggruppa per codice fustella (FS####)
        $fustelleMap = [];
        foreach ($fasi as $fase) {
            $desc = $fase->ordine->descrizione ?? '';
            $cliente = $fase->ordine->cliente_nome ?? '';
            $notePre = $fase->ordine->note_prestampa ?? '';
            $fsCodice = DescrizioneParser::parseFustella($desc, $cliente, $notePre);

            if (!$fsCodice) continue;

            // Può contenere "FS0001 / FS0002" — splittiamo
            $codici = array_map('trim', explode('/', $fsCodice));
            foreach ($codici as $codice) {
                if (!isset($fustelleMap[$codice])) {
                    $fustelleMap[$codice] = [];
                }
                $commessa = $fase->ordine->commessa;
                if (!isset($fustelleMap[$codice][$commessa])) {
                    $fustelleMap[$codice][$commessa] = [
                        'commessa' => $commessa,
                        'cliente' => $fase->ordine->cliente_nome ?? '-',
                        'descrizione' => $fase->ordine->descrizione ?? '-',
                        'data_consegna' => $fase->ordine->data_prevista_consegna,
                        'stato' => $fase->stato,
                        'fase' => $fase->faseCatalogo->reparto->nome ?? $fase->fase,
                    ];
                }
            }
        }

        // Ordina per codice fustella
        ksort($fustelleMap);

        return view('operatore.fustelle', compact('operatore', 'fustelleMap'));
    }
}