<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\Operatore;
use App\Models\Reparto;
use App\Models\FasiCatalogo;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Services\FaseStatoService;

class DashboardOwnerController extends Controller
{
    private $fasiInfo = [
        'accopp+fust' => ['avviamento' => 72, 'copieh' => 100],
        'ACCOPPIATURA.FOG.33.48INT' => ['avviamento' => 1, 'copieh' => 100],
        'ACCOPPIATURA.FOGLI' => ['avviamento' => 72, 'copieh' => 100],
        'Allest.Manuale' => ['avviamento' => 0.5, 'copieh' => 100],
        'ALLEST.SHOPPER' => ['avviamento' => 144, 'copieh' => 500],
        'ALLEST.SHOPPER030' => ['avviamento' => 144, 'copieh' => 500],
        'APPL.BIADESIVO30' => ['avviamento' => 0.5, 'copieh' => 500],
        'appl.laccetto' => ['avviamento' => 0.5, 'copieh' => 500],
        'ARROT2ANGOLI' => ['avviamento' => 0.5, 'copieh' => 100],
        'ARROT4ANGOLI' => ['avviamento' => 0.5, 'copieh' => 100],
        'AVVIAMENTISTAMPA.EST1.1' => ['avviamento' => 144, 'copieh' => 100],
        'blocchi.manuale' => ['avviamento' => 0.5, 'copieh' => 100],
        'BROSSCOPBANDELLAEST' => ['avviamento' => 72, 'copieh' => 1000],
        'BROSSCOPEST' => ['avviamento' => 72, 'copieh' => 1000],
        'BROSSFILOREFE/A4EST' => ['avviamento' => 72, 'copieh' => 1000],
        'BROSSFILOREFE/A5EST' => ['avviamento' => 72, 'copieh' => 1000],
        'BRT1' => ['avviamento' => 0.1, 'copieh' => 10000],
        'brt1' => ['avviamento' => 0.1, 'copieh' => 10000],
        'CARTONATO.GEN' => ['avviamento' => 144, 'copieh' => 1000],
        'CORDONATURAPETRATTO' => ['avviamento' => 0.5, 'copieh' => 200],
        'DEKIA-Difficile' => ['avviamento' => 0.5, 'copieh' => 200],
        'FIN01' => ['avviamento' => 0.5, 'copieh' => 4000],
        'FIN03' => ['avviamento' => 1, 'copieh' => 4000],
        'FIN04' => ['avviamento' => 1, 'copieh' => 4000],
        'FOIL.MGI.30M' => ['avviamento' => 0.5, 'copieh' => 200],
        'FOILMGI' => ['avviamento' => 0.5, 'copieh' => 200],
        'FUST.STARPACK.74X104' => ['avviamento' => 72, 'copieh' => 500],
        'FUSTBIML75X106' => ['avviamento' => 0.5, 'copieh' => 1600],
        'FUSTbIML75X106' => ['avviamento' => 0.3, 'copieh' => 2000],
        'FUSTBOBST75X106' => ['avviamento' => 0.5, 'copieh' => 3000],
        'FUSTBOBSTRILIEVI' => ['avviamento' => 0.5, 'copieh' => 3000],
        'FUSTSTELG33.44' => ['avviamento' => 0.5, 'copieh' => 1500],
        'FUSTSTELP25.35' => ['avviamento' => 0.5, 'copieh' => 1500],
        'INCOLLAGGIO.PATTINA' => ['avviamento' => 0, 'copieh' => 100],
        'INCOLLAGGIOBLOCCHI' => ['avviamento' => 0, 'copieh' => 100],
        'LAVGEN' => ['avviamento' => 0.1, 'copieh' => 100],
        'NUM.PROGR.' => ['avviamento' => 0.1, 'copieh' => 100],
        'NUM33.44' => ['avviamento' => 0.1, 'copieh' => 100],
        'PERF.BUC' => ['avviamento' => 0.1, 'copieh' => 100],
        'PI01' => ['avviamento' => 0.5, 'copieh' => 6000],
        'PI02' => ['avviamento' => 1, 'copieh' => 5000],
        'PI03' => ['avviamento' => 1, 'copieh' => 4000],
        'PIEGA2ANTECORDONE' => ['avviamento' => 0.5, 'copieh' => 500],
        'PIEGA2ANTESINGOLO' => ['avviamento' => 0.5, 'copieh' => 500],
        'PIEGA3ANTESINGOLO' => ['avviamento' => 0.5, 'copieh' => 500],
        'PIEGA8ANTESINGOLO' => ['avviamento' => 0.5, 'copieh' => 500],
        'PIEGAMANUALE' => ['avviamento' => 0.1, 'copieh' => 100],
        'PLALUX1LATO' => ['avviamento' => 0.5, 'copieh' => 1500],
        'PLALUXBV' => ['avviamento' => 0.5, 'copieh' => 1500],
        'PLAOPA1LATO' => ['avviamento' => 0.5, 'copieh' => 1500],
        'PLAOPABV' => ['avviamento' => 0.5, 'copieh' => 1500],
        'PLAPOLIESARG1LATO' => ['avviamento' => 0.5, 'copieh' => 1500],
        'PLASAB1LATO' => ['avviamento' => 0.5, 'copieh' => 1500],
        'PLASABBIA1LATO' => ['avviamento' => 0.5, 'copieh' => 1500],
        'PLASOFTBV' => ['avviamento' => 0.5, 'copieh' => 1500],
        'PLASOFTBVEST' => ['avviamento' => 0.5, 'copieh' => 1500],
        'PLASOFTTOUCH1' => ['avviamento' => 0.5, 'copieh' => 1500],
        'PUNTOMETALLICO' => ['avviamento' => 72, 'copieh' => 1500],
        'PUNTOMETALLICOEST' => ['avviamento' => 72, 'copieh' => 1500],
        'PUNTOMETALLICOESTCOPERT.' => ['avviamento' => 72, 'copieh' => 1500],
        'PUNTOMETAMANUALE' => ['avviamento' => 0.5, 'copieh' => 100],
        'RILIEVOASECCOJOH' => ['avviamento' => 0.5, 'copieh' => 2000],
        'SFUST' => ['avviamento' => 0.2, 'copieh' => 1000],
        'SFUST.IML.FUSTELLATO' => ['avviamento' => 0.2, 'copieh' => 1000],
        'SPIRBLOCCOLIBROA3' => ['avviamento' => 0.2, 'copieh' => 100],
        'SPIRBLOCCOLIBROA4' => ['avviamento' => 0.2, 'copieh' => 100],
        'SPIRBLOCCOLIBROA5' => ['avviamento' => 0.2, 'copieh' => 100],
        'STAMPA' => ['avviamento' => 0, 'copieh' => 1000],
        'STAMPA.OFFSET11.EST' => ['avviamento' => 72, 'copieh' => 1000],
        'STAMPABUSTE.EST' => ['avviamento' => 72, 'copieh' => 1000],
        'STAMPACALDOJOH' => ['avviamento' => 1, 'copieh' => 2200],
        'STAMPACALDOJOH0,1' => ['avviamento' => 1, 'copieh' => 2200],
        'STAMPAINDIGO' => ['avviamento' => 0.5, 'copieh' => 1000],
        'STAMPAINDIGOBN' => ['avviamento' => 0.5, 'copieh' => 1000],
        'STAMPAXL106' => ['avviamento' => 0.65, 'copieh' => 3900],
        'STAMPAXL106.1' => ['avviamento' => 0.65, 'copieh' => 3900],
        'STAMPAXL106.2' => ['avviamento' => 0.65, 'copieh' => 3900],
        'STAMPAXL106.3' => ['avviamento' => 0.65, 'copieh' => 3900],
        'STAMPAXL106.4' => ['avviamento' => 0.65, 'copieh' => 3900],
        'STAMPAXL106.5' => ['avviamento' => 0.65, 'copieh' => 3900],
        'STAMPAXL106.6' => ['avviamento' => 0.65, 'copieh' => 3900],
        'STAMPAXL106.7' => ['avviamento' => 0.65, 'copieh' => 3900],
        'TAGLIACARTE' => ['avviamento' => 0.5, 'copieh' => 4000],
        'TAGLIACARTE.IML' => ['avviamento' => 0.5, 'copieh' => 2000],
        'TAGLIOINDIGO' => ['avviamento' => 0.5, 'copieh' => 4000],
        'UVSERIGRAFICOEST' => ['avviamento' => 72, 'copieh' => 1000],
        'UVSPOT.MGI.30M' => ['avviamento' => 0.5, 'copieh' => 200],
        'UVSPOT.MGI.9M' => ['avviamento' => 0.5, 'copieh' => 200],
        'UVSPOTEST' => ['avviamento' => 72, 'copieh' => 1000],
        'UVSPOTSPESSEST' => ['avviamento' => 72, 'copieh' => 1000],
        'ZUND' => ['avviamento' => 0.5, 'copieh' => 50],
        'APPL.CORDONCINO0,035' => ['avviamento' => 0.02, 'copieh' => 50],
        // Nuove fasi (valori da fasi simili)
        '4graph' => ['avviamento' => 0.5, 'copieh' => 100],           // come Allest.Manuale (esterno)
        'stampalaminaoro' => ['avviamento' => 1, 'copieh' => 2200],   // come STAMPACALDOJOH
        'ALL.COFANETTO.ISMAsrl' => ['avviamento' => 0.5, 'copieh' => 100], // come Allest.Manuale (esterno)
        'PMDUPLO36COP' => ['avviamento' => 0.5, 'copieh' => 100],    // esterno generico
        'FINESTRATURA.MANUALE' => ['avviamento' => 0.5, 'copieh' => 100], // come FINESTRATURA.INT
        'FINESTRATURA.INT' => ['avviamento' => 0.5, 'copieh' => 100],
        'STAMPACALDOJOHEST' => ['avviamento' => 72, 'copieh' => 2200], // come STAMPACALDOJOH ma est (avv.72)
        'BROSSFRESATA/A5EST' => ['avviamento' => 72, 'copieh' => 1000], // come BROSSFILOREFE/A5EST
        'PIEGA6ANTESINGOLO' => ['avviamento' => 0.5, 'copieh' => 500], // come PIEGA3ANTESINGOLO
        'ALLESTIMENTO.ESPOSITORI' => ['avviamento' => 0.5, 'copieh' => 100], // come Allest.Manuale
        'FUSTIML75X106' => ['avviamento' => 0.5, 'copieh' => 3000],   // come FUSTBOBST75X106
        'FUSTELLATURA72X51' => ['avviamento' => 0.5, 'copieh' => 1500], // come FUSTSTELG33.44
    ];

public function calcolaOreEPriorita($fase)
    {
        $qta_carta = $fase->ordine->qta_carta ?: 0;
        $infoFase = $this->fasiInfo[$fase->fase] ?? ['avviamento' => 0, 'copieh' => 0];
        $copieh = $infoFase['copieh'] ?: 1;

        // ore = avviamento + qtaCarta / copieh (pezzi da fare / pezzi all'ora)
        $fase->ore = $infoFase['avviamento'] + ($qta_carta / $copieh);

        // giorni rimasti = dataPrevConsegna - OGGI (negativo se scaduta)
        $giorni_rimasti = 0;
        if ($fase->ordine->data_prevista_consegna) {
            $oggi = Carbon::today();
            $consegna = Carbon::parse($fase->ordine->data_prevista_consegna)->startOfDay();
            $giorni_rimasti = ($consegna->timestamp - $oggi->timestamp) / 86400;
        }

        // Ordine fase dalla config (es. STAMPA=10, PLASTIFICA=20, BRT=999)
        $fasePriorita = config('fasi_priorita')[$fase->fase] ?? 500;

        // Priorità = giorni rimasti - ore/24 + ordineFase/10000
        $prioritaCalcolata = round($giorni_rimasti - ($fase->ore / 24) + ($fasePriorita / 10000), 2);

        $fase->priorita = $prioritaCalcolata;
        return $fase;
    }

    public function index()
    {
        $fasi = OrdineFase::with(['ordine', 'faseCatalogo', 'operatori' => fn($q) => $q->select('operatori.id', 'nome')])
            ->where('stato', '!=', 3)
            ->get()
            ->map(function ($fase) {
                $fase = $this->calcolaOreEPriorita($fase);

                if ($fase->operatori->isNotEmpty()) {
                    $primaData = $fase->operatori->sortBy('pivot.data_inizio')->first()->pivot->data_inizio;
                    $fase->data_inizio = $primaData ? Carbon::parse($primaData)->format('d/m/Y H:i:s') : null;
                } else {
                    $fase->data_inizio = null;
                }

                return $fase;
            })
            ->sortBy('priorita');

        $ultimoNumero = Operatore::selectRaw('CAST(RIGHT(codice_operatore, 3) AS UNSIGNED) as numero')
            ->orderByDesc('numero')->value('numero');
        $prossimoNumero = $ultimoNumero ? $ultimoNumero + 1 : 1;
        $prossimoCodice = '__' . str_pad($prossimoNumero, 3, '0', STR_PAD_LEFT);

        $reparti = Reparto::orderBy('nome')->pluck('nome', 'id');
        $fasiCatalogo = FasiCatalogo::all();

        // Report spedizioni di oggi
        $repartoSpedizione = Reparto::where('nome', 'spedizione')->first();
        $spedizioniOggi = collect();
        if ($repartoSpedizione) {
            $spedizioniOggi = OrdineFase::where('stato', 3)
                ->whereDate('data_fine', Carbon::today())
                ->whereHas('faseCatalogo', function ($q) use ($repartoSpedizione) {
                    $q->where('reparto_id', $repartoSpedizione->id);
                })
                ->with(['ordine', 'faseCatalogo', 'operatori'])
                ->get()
                ->sortByDesc('data_fine');
        }

        return view('owner.dashboard', compact('fasi', 'prossimoNumero', 'prossimoCodice', 'reparti', 'fasiCatalogo', 'spedizioniOggi'));
    }

    public function aggiornaCampo(Request $request)
    {
        $campiFase = ['qta_prod', 'note', 'stato', 'data_inizio', 'data_fine', 'ore', 'priorita', 'fase'];
        $campiOrdine = ['cliente_nome', 'cod_art', 'descrizione', 'qta_richiesta', 'um',
                        'data_registrazione', 'data_prevista_consegna',
                        'cod_carta', 'carta', 'qta_carta', 'UM_carta'];

        $validator = Validator::make($request->all(), [
            'fase_id' => 'required|exists:ordine_fasi,id',
            'campo' => 'required|string',
            'valore' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $fase = OrdineFase::with('ordine')->find($request->fase_id);
        $campo = $request->campo;
        $valore = $request->valore;

        if (in_array($campo, ['data_registrazione','data_prevista_consegna','data_inizio','data_fine'])) {
            try {
                $valore = $valore ? Carbon::createFromFormat('d/m/Y H:i:s', $valore)->format('Y-m-d H:i:s') : null;
            } catch (\Exception $e1) {
                try {
                    $valore = $valore ? Carbon::createFromFormat('d/m/Y', $valore)->format('Y-m-d') : null;
                } catch (\Exception $e2) {
                    return response()->json(['success' => false, 'messaggio' => 'Formato data non valido'], 422);
                }
            }
        }

        $campiNumerici = ['qta_prod', 'qta_carta', 'qta_richiesta', 'priorita', 'ore'];
        if (in_array($campo, $campiNumerici)) {
            $valore = $valore !== null ? (float)str_replace(',', '.', $valore) : 0;
        }

        if (in_array($campo, $campiFase)) {
            $fase->{$campo} = $valore;
            $fase->save();

            // Se aggiornata qta_prod, controlla completamento automatico
            if ($campo === 'qta_prod') {
                FaseStatoService::controllaCompletamento($fase->id);
            }
        } elseif (in_array($campo, $campiOrdine)) {
            $fase->ordine->{$campo} = $valore;
            $fase->ordine->save();

            // Se cambiata data consegna, aggiorna TUTTI gli ordini della stessa commessa
            if ($campo === 'data_prevista_consegna') {
                Ordine::where('commessa', $fase->ordine->commessa)
                    ->where('id', '!=', $fase->ordine->id)
                    ->update(['data_prevista_consegna' => $valore]);

                FaseStatoService::ricalcolaStati($fase->ordine_id);

                return response()->json(['success' => true, 'reload' => true]);
            }
        } else {
            return response()->json(['success' => false, 'messaggio' => 'Campo non aggiornabile'], 422);
        }

        return response()->json(['success' => true]);
    }

    public function aggiungiOperatore(Request $request)
    {
        $request->validate([
            'nome' => 'required|string',
            'cognome' => 'required|string',
            'ruolo' => 'required|in:operatore,owner',
            'reparto_principale' => 'required|exists:reparti,id',
            'reparto_secondario' => 'nullable|exists:reparti,id',
        ]);

        $ultimoNumero = Operatore::selectRaw('CAST(RIGHT(codice_operatore, 3) AS UNSIGNED) as numero')
            ->orderByDesc('numero')->value('numero');
        $numero = $ultimoNumero ? $ultimoNumero + 1 : 1;

        $nome = ucfirst(strtolower($request->nome));
        $cognome = ucfirst(strtolower($request->cognome));
        $iniziali = strtoupper($nome[0] . $cognome[0]);
        $codice = $iniziali . str_pad($numero, 3, '0', STR_PAD_LEFT);

        $operatore = Operatore::create([
            'nome' => $nome,
            'cognome' => $cognome,
            'codice_operatore' => $codice,
            'ruolo' => $request->ruolo,
            'attivo' => 1,
            'reparto_id' => $request->reparto_principale
        ]);

        $reparti = array_filter([$request->reparto_principale, $request->reparto_secondario]);
        $operatore->reparti()->sync($reparti);

        return redirect()->back()->with('success', "Operatore $codice aggiunto correttamente");
    }

    public function aggiungiRiga(Request $request)
    {
        $faseCatalogo = $request->fase_catalogo_id
            ? FasiCatalogo::with('reparto')->find($request->fase_catalogo_id)
            : null;

        $ordine = Ordine::create([
            'commessa' => trim($request->commessa),
            'cliente_nome' => trim($request->cliente_nome ?? ''),
            'cod_art' => trim($request->cod_art ?? ''),
            'descrizione' => trim($request->descrizione ?? ''),
            'qta_richiesta' => $request->qta_richiesta ?? 0,
            'um' => $request->um ?? 'FG',
            'stato' => 0,
            'data_registrazione' => now()->toDateString(),
            'data_prevista_consegna' => $request->data_prevista_consegna ?? null,
            'priorita' => $request->priorita ?? 0,
        ]);

        OrdineFase::create([
            'ordine_id' => $ordine->id,
            'fase' => $faseCatalogo ? $faseCatalogo->nome : '-',
            'fase_catalogo_id' => $faseCatalogo?->id,
            'stato' => 0,
        ]);

        return redirect()->back()->with('success', 'Riga aggiunta correttamente.');
    }

    public function importOrdini(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);
        $file = $request->file('file');
        if (!$file->isValid()) return redirect()->back()->with('error', 'File non valido.');

        try {
            $path = $file->store('imports');
            Excel::import(new \App\Imports\OrdiniImport, storage_path('app/' . $path));
            @unlink(storage_path('app/' . $path));
            FaseStatoService::ricalcolaTutti();
            return redirect()->route('owner.dashboard')->with('success', 'Ordini importati correttamente.');
        } catch (\Exception $e) {
            return redirect()->route('owner.dashboard')->with('error', 'Errore importazione: ' . $e->getMessage());
        }
    }

    public function fasiTerminate()
{
    $fasiTerminate = OrdineFase::with([
            'ordine.reparto',
            'faseCatalogo',
            'operatori'
        ])
        ->where('stato', 3)
        ->get()
        ->map(function ($fase) {

            // Calcolo ore e priorità
            $fase = $this->calcolaOreEPriorita($fase);

            // Reparto: SOLO nome
            $fase->reparto_nome = $fase->ordine->reparto->nome ?? '-';

            // Default
            $fase->data_inizio = null;
            $fase->data_fine = null;

            if ($fase->operatori->isNotEmpty()) {

                // DATA INIZIO = primo operatore che ha iniziato
                $primaDataInizio = $fase->operatori
                    ->whereNotNull('pivot.data_inizio')
                    ->sortBy('pivot.data_inizio')
                    ->first()?->pivot->data_inizio;

                if ($primaDataInizio) {
                    $fase->data_inizio = Carbon::parse($primaDataInizio)
                        ->format('d/m/Y H:i:s');
                }

                // DATA FINE = primo operatore che ha cliccato termina
                $primaDataFine = $fase->operatori
                    ->whereNotNull('pivot.data_fine')
                    ->sortBy('pivot.data_fine')
                    ->first()?->pivot->data_fine;

                if ($primaDataFine) {
                    $fase->data_fine = Carbon::parse($primaDataFine)
                        ->format('d/m/Y H:i:s');
                }
            }

            return $fase;
        })
        ->sortBy('priorita');

    return view('owner.fasi_terminate', compact('fasiTerminate'));
}

    public function dettaglioCommessa($commessa)
    {
        $ordini = Ordine::where('commessa', $commessa)->with('fasi.faseCatalogo.reparto', 'fasi.operatori')->get();
        if ($ordini->isEmpty()) abort(404, 'Commessa non trovata');

        $fasi = $ordini->flatMap(function ($ordine) {
            return $ordine->fasi->map(function ($fase) use ($ordine) {
                $fase->ordine = $ordine;
                $fase->reparto_nome = $fase->faseCatalogo->reparto->nome ?? '-';
                $fase = $this->calcolaOreEPriorita($fase);
                return $fase;
            });
        })->sortBy('priorita');

        return view('owner.dettaglio_commessa', compact('commessa', 'fasi'));
    }

    public function eliminaFase(Request $request)
    {
        $fase = OrdineFase::find($request->fase_id);
        if (!$fase) return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);

        $fase->operatori()->detach();
        $fase->delete();

        return response()->json(['success' => true]);
    }

    public function aggiornaStato(Request $request)
    {
        $fase = OrdineFase::find($request->fase_id);
        if (!$fase) return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);

        $nuovoStato = (int) $request->stato;
        $fase->stato = $nuovoStato;

        if ($nuovoStato == 3 && !$fase->data_fine) {
            $fase->data_fine = now()->format('d/m/Y H:i:s');
        }

        $fase->save();

        // Ricalcola stati delle fasi successive
        FaseStatoService::ricalcolaStati($fase->ordine_id);

        return response()->json(['success' => true, 'messaggio' => 'Stato aggiornato']);
    }

    public function ricalcolaStati()
    {
        FaseStatoService::ricalcolaTutti();
        return response()->json(['success' => true, 'messaggio' => 'Stati ricalcolati']);
    }
}