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
        $nomiReparti = $operatore->reparti->pluck('nome')->map(fn($n) => strtolower($n))->toArray();

        // Operatori prestampa → redirect alla dashboard prestampa
        if (in_array('prestampa', $nomiReparti)) {
            return redirect()->route('operatore.prestampa', ['op_token' => $request->get('op_token')]);
        }

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
            ->sortBy('priorita');

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

    /**
     * Dashboard Prestampa: lista commesse attive con commessa + descrizione cliccabili.
     */
    public function prestampa(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();
        if (!$operatore) abort(403, 'Accesso negato');

        // Commesse con almeno una fase attiva (stato < 3), raggruppate per commessa (1 riga per commessa)
        $commesse = \App\Models\Ordine::whereHas('fasi', fn($q) => $q->where('stato', '<', 3))
            ->select('commessa',
                \Illuminate\Support\Facades\DB::raw('MIN(cliente_nome) as cliente_nome'),
                \Illuminate\Support\Facades\DB::raw('MIN(descrizione) as descrizione'),
                \Illuminate\Support\Facades\DB::raw('GROUP_CONCAT(DISTINCT descrizione SEPARATOR " | ") as tutte_descrizioni'),
                \Illuminate\Support\Facades\DB::raw('MIN(data_prevista_consegna) as data_prevista_consegna'),
                \Illuminate\Support\Facades\DB::raw('MIN(data_registrazione) as data_registrazione'),
                \Illuminate\Support\Facades\DB::raw('MAX(note_prestampa) as note_prestampa'),
                \Illuminate\Support\Facades\DB::raw('MAX(responsabile) as responsabile'),
                \Illuminate\Support\Facades\DB::raw('SUM(qta_richiesta) as qta_richiesta'))
            ->groupBy('commessa')
            ->orderBy('data_prevista_consegna')
            ->get();

        // Mirko D'Orazio: mostra solo commesse senza FS o con 2+ FS
        $isMirko = strtolower(trim($operatore->cognome ?? '')) === "d'orazio"
                || strtolower(trim($operatore->cognome ?? '')) === 'dorazio'
                || strtolower(trim($operatore->cognome ?? '')) === 'd orazio';

        if ($isMirko) {
            $commesse = $commesse->filter(function ($c) {
                $desc = $c->tutte_descrizioni ?? $c->descrizione ?? '';
                $cliente = $c->cliente_nome ?? '';
                $notePre = $c->note_prestampa ?? '';
                $fs = \App\Helpers\DescrizioneParser::parseFustella($desc, $cliente, $notePre);

                // Nessuna fustella trovata
                if (!$fs || $fs === '-') return true;

                // Due o più fustelle (contiene "/" o ",")
                if (str_contains($fs, '/') || str_contains($fs, ',')) return true;

                return false;
            })->values();
        }

        return view('operatore.prestampa', compact('operatore', 'commesse'));
    }

    /**
     * Dettaglio commessa prestampa: stessa vista owner ma con campi editabili per prestampa.
     */
    public function prestampaDettaglio(Request $request, $commessa)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();
        if (!$operatore) abort(403, 'Accesso negato');

        $ordini = \App\Models\Ordine::where('commessa', $commessa)
            ->with('fasi.faseCatalogo.reparto', 'fasi.operatori')
            ->get();

        if ($ordini->isEmpty()) abort(404, 'Commessa non trovata');

        $ordine = $ordini->first();

        $fasi = $ordini->flatMap(function ($ordine) {
            return $ordine->fasi->map(function ($fase) use ($ordine) {
                $fase->ordine = $ordine;
                $fase->reparto_nome = $fase->faseCatalogo->reparto->nome ?? '-';
                return $fase;
            });
        })->sortBy(function ($fase) {
            $ordine = config('fasi_ordine');
            $nome = $fase->faseCatalogo->nome ?? '';
            return $ordine[$nome] ?? $ordine[strtolower($nome)] ?? 999;
        });

        $isMirko = strtolower(trim($operatore->cognome ?? '')) === "d'orazio"
                || strtolower(trim($operatore->cognome ?? '')) === 'dorazio'
                || strtolower(trim($operatore->cognome ?? '')) === 'd orazio';

        return view('operatore.prestampa_dettaglio', compact('operatore', 'ordine', 'ordini', 'commessa', 'fasi', 'isMirko'));
    }

    /**
     * Aggiorna campo ordine da prestampa (note_prestampa, responsabile, commento_produzione).
     */
    public function prestampaAggiornaCampo(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();
        if (!$operatore) return response()->json(['success' => false, 'messaggio' => 'Non autorizzato'], 403);

        $campo = $request->input('campo');
        $valore = $request->input('valore');
        $faseId = $request->input('fase_id');
        $ordineId = $request->input('ordine_id');

        // Aggiornamento nota su singola fase (usato da Mirko)
        if ($faseId && $campo === 'note') {
            $fase = \App\Models\OrdineFase::find($faseId);
            if (!$fase) return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
            $fase->note = $valore ?: null;
            $fase->save();
            return response()->json(['success' => true]);
        }

        // Aggiornamento campo su ordine
        $campiConsentiti = ['note_prestampa', 'responsabile', 'commento_produzione',
                           'descrizione', 'cliente_nome', 'qta_richiesta', 'colori', 'fustella_codice'];

        if (!in_array($campo, $campiConsentiti)) {
            return response()->json(['success' => false, 'messaggio' => 'Campo non consentito']);
        }

        $ordine = \App\Models\Ordine::find($ordineId);
        if (!$ordine) {
            return response()->json(['success' => false, 'messaggio' => 'Ordine non trovato']);
        }

        if ($campo === 'qta_richiesta') {
            $valore = (int) str_replace(['.', ','], ['', ''], $valore);
        }

        \App\Models\Ordine::where('commessa', $ordine->commessa)->update([$campo => $valore]);

        return response()->json(['success' => true]);
    }

    /**
     * Sync Onda dalla prestampa.
     */
    public function prestampaSyncOnda(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();
        if (!$operatore) abort(403);

        try {
            $risultato = \App\Services\OndaSyncService::sincronizza();
            $msg = "Sync Onda: {$risultato['ordini_creati']} creati, {$risultato['ordini_aggiornati']} aggiornati, {$risultato['fasi_create']} fasi.";
            return redirect()->route('operatore.prestampa', ['op_token' => $request->get('op_token')])->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->route('operatore.prestampa', ['op_token' => $request->get('op_token')])->with('error', 'Errore sync: ' . $e->getMessage());
        }
    }
}