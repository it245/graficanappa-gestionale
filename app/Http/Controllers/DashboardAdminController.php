<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Operatore;
use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\Reparto;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardAdminController extends Controller
{
    public function index()
    {
        $operatori = Operatore::with('reparti')->orderBy('attivo', 'desc')->orderBy('nome')->get();
        $reparti = Reparto::orderBy('nome')->pluck('nome', 'id');

        $ultimoNumero = Operatore::selectRaw('CAST(RIGHT(codice_operatore, 3) AS UNSIGNED) as numero')
            ->orderByDesc('numero')->value('numero');
        $prossimoNumero = $ultimoNumero ? $ultimoNumero + 1 : 1;
        $prossimoCodice = '__' . str_pad($prossimoNumero, 3, '0', STR_PAD_LEFT);

        return view('admin.dashboard', compact('operatori', 'reparti', 'prossimoCodice', 'prossimoNumero'));
    }

    public function crea()
    {
        $reparti = Reparto::orderBy('nome')->pluck('nome', 'id');
        return view('admin.operatore_form', [
            'operatore' => null,
            'reparti' => $reparti,
        ]);
    }

    public function salva(Request $request)
    {
        $request->validate([
            'nome' => 'required|string',
            'cognome' => 'required|string',
            'ruolo' => 'required|in:operatore,owner,admin',
            'reparto_principale' => 'required|exists:reparti,id',
            'reparto_secondario' => 'nullable|exists:reparti,id',
            'password' => 'nullable|string|min:4',
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
            'reparto_id' => $request->reparto_principale,
            'password' => $request->password ? Hash::make($request->password) : null,
        ]);

        $reparti = array_filter([$request->reparto_principale, $request->reparto_secondario]);
        $operatore->reparti()->sync($reparti);

        return redirect()->route('admin.dashboard')->with('success', "Operatore $codice creato correttamente.");
    }

    public function modifica($id)
    {
        $operatore = Operatore::with('reparti')->findOrFail($id);
        $reparti = Reparto::orderBy('nome')->pluck('nome', 'id');
        return view('admin.operatore_form', compact('operatore', 'reparti'));
    }

    public function aggiorna(Request $request, $id)
    {
        $operatore = Operatore::findOrFail($id);

        $request->validate([
            'nome' => 'required|string',
            'cognome' => 'required|string',
            'ruolo' => 'required|in:operatore,owner,admin',
            'reparto_principale' => 'required|exists:reparti,id',
            'reparto_secondario' => 'nullable|exists:reparti,id',
            'password' => 'nullable|string|min:4',
        ]);

        $operatore->nome = ucfirst(strtolower($request->nome));
        $operatore->cognome = ucfirst(strtolower($request->cognome));
        $operatore->ruolo = $request->ruolo;
        $operatore->reparto_id = $request->reparto_principale;

        if ($request->filled('password')) {
            $operatore->password = Hash::make($request->password);
        }

        $operatore->save();

        $reparti = array_filter([$request->reparto_principale, $request->reparto_secondario]);
        $operatore->reparti()->sync($reparti);

        return redirect()->route('admin.dashboard')->with('success', "Operatore {$operatore->codice_operatore} aggiornato.");
    }

    public function statistiche()
    {
        $sogliaRecenti = Carbon::now()->subDays(7);

        $operatori = Operatore::where('attivo', 1)
            ->where('ruolo', 'operatore')
            ->with('reparti')
            ->get()
            ->map(function ($op) use ($sogliaRecenti) {
                // Reparti come stringa
                $op->stat_reparti = $op->reparti->pluck('nome')->join(', ');

                // Fasi completate (stato=3) e in corso (stato=2) dalla pivot fase_operatore
                $fasi = DB::table('fase_operatore')
                    ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
                    ->where('fase_operatore.operatore_id', $op->id)
                    ->select(
                        'ordine_fasi.stato',
                        'ordine_fasi.qta_prod',
                        'fase_operatore.data_inizio',
                        'fase_operatore.data_fine'
                    )
                    ->get();

                $op->stat_fasi_completate = $fasi->filter(fn($f) => $f->stato >= 3)->count();
                $op->stat_fasi_in_corso = $fasi->where('stato', 2)->count();

                // Tempo totale lavorato (somma differenze data_inizio - data_fine dalla pivot)
                $secTotale = 0;
                $fasiConTempo = 0;
                foreach ($fasi as $f) {
                    if ($f->data_inizio && $f->data_fine) {
                        $sec = Carbon::parse($f->data_fine)->diffInSeconds(Carbon::parse($f->data_inizio));
                        $secTotale += $sec;
                        $fasiConTempo++;
                    }
                }
                $op->stat_sec_totale = $secTotale;
                $op->stat_sec_medio = $fasiConTempo > 0 ? round($secTotale / $fasiConTempo) : 0;

                // Quantita prodotta totale
                $op->stat_qta_prod = $fasi->filter(fn($f) => $f->stato >= 3)->sum('qta_prod');

                // Ultimi 7 giorni
                $recenti = $fasi->filter(function ($f) use ($sogliaRecenti) {
                    return $f->stato >= 3 && $f->data_fine && Carbon::parse($f->data_fine)->gte($sogliaRecenti);
                });
                $op->stat_fasi_recenti = $recenti->count();
                $secRecenti = 0;
                foreach ($recenti as $f) {
                    if ($f->data_inizio && $f->data_fine) {
                        $secRecenti += Carbon::parse($f->data_fine)->diffInSeconds(Carbon::parse($f->data_inizio));
                    }
                }
                $op->stat_sec_recenti = $secRecenti;

                return $op;
            })
            ->sortByDesc('stat_fasi_completate');

        return view('admin.statistiche_operatori', compact('operatori'));
    }

    public function toggleAttivo($id)
    {
        $operatore = Operatore::findOrFail($id);
        $operatore->attivo = !$operatore->attivo;
        $operatore->save();

        $stato = $operatore->attivo ? 'attivato' : 'disattivato';
        return redirect()->route('admin.dashboard')->with('success', "Operatore {$operatore->codice_operatore} $stato.");
    }

    // Tabella costanti fasi (duplicata da DashboardOwnerController)
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
        'PIEGA8TTAVO' => ['avviamento' => 0.5, 'copieh' => 500],
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
        '4graph' => ['avviamento' => 0.5, 'copieh' => 100],
        'stampalaminaoro' => ['avviamento' => 1, 'copieh' => 2200],
        'STAMPALAMINAORO' => ['avviamento' => 1, 'copieh' => 2200],
        'ALL.COFANETTO.ISMAsrl' => ['avviamento' => 0.5, 'copieh' => 100],
        'PMDUPLO36COP' => ['avviamento' => 0.5, 'copieh' => 100],
        'FINESTRATURA.MANUALE' => ['avviamento' => 0.5, 'copieh' => 100],
        'FINESTRATURA.INT' => ['avviamento' => 0.5, 'copieh' => 100],
        'STAMPACALDOJOHEST' => ['avviamento' => 72, 'copieh' => 2200],
        'BROSSFRESATA/A5EST' => ['avviamento' => 72, 'copieh' => 1000],
        'PIEGA6ANTESINGOLO' => ['avviamento' => 0.5, 'copieh' => 500],
        'ALLESTIMENTO.ESPOSITORI' => ['avviamento' => 0.5, 'copieh' => 100],
        'FUSTIML75X106' => ['avviamento' => 0.5, 'copieh' => 3000],
        'FUSTELLATURA72X51' => ['avviamento' => 0.5, 'copieh' => 1500],
        'est STAMPACALDOJOH' => ['avviamento' => 72, 'copieh' => 2200],
        'est FUSTSTELG33.44' => ['avviamento' => 72, 'copieh' => 1500],
        'est FUSTBOBST75X106' => ['avviamento' => 72, 'copieh' => 3000],
        'STAMPA.ESTERNA' => ['avviamento' => 72, 'copieh' => 1000],
        'EXTALL.COFANETTO.LEGOKART' => ['avviamento' => 72, 'copieh' => 100],
        'EXTAllest.Manuale' => ['avviamento' => 72, 'copieh' => 100],
        'EXTALLEST.SHOPPER' => ['avviamento' => 72, 'copieh' => 500],
        'EXTALLESTIMENTO.ESPOSITOR' => ['avviamento' => 72, 'copieh' => 100],
        'EXTAPPL.CORDONCINO0,035' => ['avviamento' => 72, 'copieh' => 50],
        'EXTAVVIAMENTISTAMPA.EST1.' => ['avviamento' => 72, 'copieh' => 100],
        'EXTBROSSCOPEST' => ['avviamento' => 72, 'copieh' => 1000],
        'EXTBROSSFILOREFE/A4EST' => ['avviamento' => 72, 'copieh' => 1000],
        'EXTBROSSFILOREFE/A5EST' => ['avviamento' => 72, 'copieh' => 1000],
        'EXTBROSSFRESATA/A4EST' => ['avviamento' => 72, 'copieh' => 1000],
        'EXTBROSSFRESATA/A5EST' => ['avviamento' => 72, 'copieh' => 1000],
        'EXTCARTONATO' => ['avviamento' => 72, 'copieh' => 1000],
        'EXTCARTONATO.GEN' => ['avviamento' => 72, 'copieh' => 1000],
        'EXTFUSTELLATURA72X51' => ['avviamento' => 72, 'copieh' => 1500],
        'EXTPUNTOMETALLICOEST' => ['avviamento' => 72, 'copieh' => 1500],
        'EXTSTAMPA.OFFSET11.EST' => ['avviamento' => 72, 'copieh' => 1000],
        'EXTSTAMPABUSTE.EST' => ['avviamento' => 72, 'copieh' => 1000],
        'EXTSTAMPASECCO' => ['avviamento' => 72, 'copieh' => 2000],
        'EXTUVSPOTEST' => ['avviamento' => 72, 'copieh' => 1000],
        'EXTUVSPOTSPESSEST' => ['avviamento' => 72, 'copieh' => 1000],
        'DEKIA-semplice' => ['avviamento' => 0.5, 'copieh' => 200],
        'STAMPASECCO' => ['avviamento' => 0.5, 'copieh' => 2000],
        'STAMPACALDO04' => ['avviamento' => 1, 'copieh' => 2200],
        'STAMPACALDOBR' => ['avviamento' => 1, 'copieh' => 2200],
        'STAMPAINDIGOBIANCO' => ['avviamento' => 0.5, 'copieh' => 1000],
    ];

    private function calcolaOreStimate($faseName, $qtaCarta)
    {
        $info = $this->fasiInfo[$faseName] ?? ['avviamento' => 0.5, 'copieh' => 1000];
        $copieh = $info['copieh'] ?: 1000;
        return $info['avviamento'] + ($qtaCarta / $copieh);
    }

    public function listaCommesse(Request $request)
    {
        $filtro = $request->get('filtro', 'tutte');
        $ricercaCliente = $request->get('cliente', '');
        $dataDa = $request->get('data_da', '');
        $dataA = $request->get('data_a', '');

        $commesse = Ordine::select('commessa', 'cliente_nome', 'descrizione', 'data_prevista_consegna')
            ->groupBy('commessa', 'cliente_nome', 'descrizione', 'data_prevista_consegna')
            ->get()
            ->map(function ($row) {
                $fasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))->get();
                $row->fasi_totali = $fasi->count();
                $row->fasi_completate = $fasi->filter(fn($f) => $f->stato >= 3)->count();
                $row->percentuale = $row->fasi_totali > 0 ? round(($row->fasi_completate / $row->fasi_totali) * 100) : 0;
                $row->completata = $row->fasi_totali > 0 && $row->fasi_completate === $row->fasi_totali;
                $row->consegnata = $fasi->contains(fn($f) => $f->stato == 4);

                if ($row->consegnata) {
                    $row->stato_label = 'Consegnata';
                } elseif ($row->completata) {
                    $row->stato_label = 'Completata';
                } else {
                    $row->stato_label = 'In corso';
                }

                return $row;
            });

        if ($filtro === 'completate') {
            $commesse = $commesse->filter(fn($c) => $c->completata);
        } elseif ($filtro === 'in_corso') {
            $commesse = $commesse->filter(fn($c) => !$c->completata && !$c->consegnata);
        } elseif ($filtro === 'consegnate') {
            $commesse = $commesse->filter(fn($c) => $c->consegnata);
        }

        if ($ricercaCliente) {
            $ricerca = strtolower($ricercaCliente);
            $commesse = $commesse->filter(fn($c) => str_contains(strtolower($c->cliente_nome ?? ''), $ricerca));
        }

        if ($dataDa) {
            $da = Carbon::parse($dataDa)->startOfDay();
            $commesse = $commesse->filter(fn($c) => $c->data_prevista_consegna && Carbon::parse($c->data_prevista_consegna)->gte($da));
        }
        if ($dataA) {
            $a = Carbon::parse($dataA)->endOfDay();
            $commesse = $commesse->filter(fn($c) => $c->data_prevista_consegna && Carbon::parse($c->data_prevista_consegna)->lte($a));
        }

        $commesse = $commesse->sortByDesc('data_prevista_consegna')->values();

        return view('admin.lista_commesse', compact('commesse', 'filtro', 'ricercaCliente', 'dataDa', 'dataA'));
    }

    public function reportCommessa($commessa)
    {
        $ordini = Ordine::where('commessa', $commessa)
            ->with('fasi.faseCatalogo.reparto', 'fasi.operatori')
            ->get();

        if ($ordini->isEmpty()) abort(404, 'Commessa non trovata');

        $primoOrdine = $ordini->first();
        $fasiReport = collect();

        $totaleOreStimate = 0;
        $totaleOreEffettive = 0;

        foreach ($ordini as $ordine) {
            $qtaCarta = $ordine->qta_carta ?: 0;

            foreach ($ordine->fasi as $fase) {
                $oreStimate = $this->calcolaOreStimate($fase->fase, $qtaCarta);

                // Ore effettive dalla pivot fase_operatore
                $oreEffettive = 0;
                if ($fase->operatori->isNotEmpty()) {
                    $dataInizio = $fase->operatori
                        ->whereNotNull('pivot.data_inizio')
                        ->sortBy('pivot.data_inizio')
                        ->first()?->pivot->data_inizio;

                    $dataFine = $fase->operatori
                        ->whereNotNull('pivot.data_fine')
                        ->sortByDesc('pivot.data_fine')
                        ->first()?->pivot->data_fine;

                    if ($dataInizio && $dataFine) {
                        $oreEffettive = Carbon::parse($dataFine)->diffInSeconds(Carbon::parse($dataInizio)) / 3600;
                    }
                }

                $delta = $oreEffettive - $oreStimate;
                $percentualeScostamento = $oreStimate > 0 ? round(($delta / $oreStimate) * 100, 1) : 0;

                $nomiOperatori = $fase->operatori->pluck('nome')->join(', ') ?: '-';
                $repartoNome = $fase->faseCatalogo->reparto->nome ?? '-';

                $fasiReport->push((object)[
                    'fase' => $fase->fase,
                    'reparto' => $repartoNome,
                    'operatore' => $nomiOperatori,
                    'qta' => $ordine->qta_carta ?: $ordine->qta_richiesta,
                    'ore_stimate' => round($oreStimate, 2),
                    'ore_effettive' => round($oreEffettive, 2),
                    'delta' => round($delta, 2),
                    'percentuale' => $percentualeScostamento,
                    'stato' => $fase->stato,
                ]);

                $totaleOreStimate += $oreStimate;
                $totaleOreEffettive += $oreEffettive;
            }
        }

        $tutteCompletate = $ordini->flatMap->fasi->every(fn($f) => $f->stato >= 3);
        $deltaComplessivo = $totaleOreEffettive - $totaleOreStimate;
        $percentualeComplessiva = $totaleOreStimate > 0 ? round(($deltaComplessivo / $totaleOreStimate) * 100, 1) : 0;

        return view('admin.report_commessa', [
            'commessa' => $commessa,
            'cliente' => $primoOrdine->cliente_nome,
            'descrizione' => $primoOrdine->descrizione,
            'consegna' => $primoOrdine->data_prevista_consegna,
            'completata' => $tutteCompletate,
            'fasi' => $fasiReport,
            'totaleOreStimate' => round($totaleOreStimate, 2),
            'totaleOreEffettive' => round($totaleOreEffettive, 2),
            'deltaComplessivo' => round($deltaComplessivo, 2),
            'percentualeComplessiva' => $percentualeComplessiva,
        ]);
    }

    public function reportProduzione()
    {
        $da = Carbon::now()->subDays(7);
        $oggi = Carbon::now();

        // Fasi completate negli ultimi 7 giorni
        $fasiCompletate7gg = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->where('ordine_fasi.stato', '>=', 3)
            ->where('fase_operatore.data_fine', '>=', $da)
            ->count();

        // Ore lavorate negli ultimi 7 giorni
        $pivotRecenti = DB::table('fase_operatore')
            ->where('data_fine', '>=', $da)
            ->whereNotNull('data_inizio')
            ->whereNotNull('data_fine')
            ->get();

        $secLavorati = 0;
        foreach ($pivotRecenti as $p) {
            $secLavorati += Carbon::parse($p->data_fine)->diffInSeconds(Carbon::parse($p->data_inizio));
        }
        $oreLavorate = round($secLavorati / 3600, 1);

        // Commesse spedite (tutte le fasi stato>=3) negli ultimi 7 giorni
        $commesseSpedite = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->where('ordine_fasi.stato', '>=', 3)
            ->where('fase_operatore.data_fine', '>=', $da)
            ->select('ordini.commessa')
            ->distinct()
            ->get()
            ->filter(function ($row) {
                $fasiNonComplete = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))
                    ->where('stato', '<', 3)->count();
                return $fasiNonComplete === 0;
            })
            ->count();

        // Commesse in ritardo (data_prevista_consegna < oggi e non tutte completate)
        $commesseInRitardo = Ordine::where('data_prevista_consegna', '<', $oggi->toDateString())
            ->select('commessa', 'cliente_nome', 'data_prevista_consegna')
            ->groupBy('commessa', 'cliente_nome', 'data_prevista_consegna')
            ->get()
            ->filter(function ($row) {
                $fasiNonComplete = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))
                    ->where('stato', '<', 3)->count();
                return $fasiNonComplete > 0;
            })
            ->map(function ($row) use ($oggi) {
                $row->giorni_ritardo = Carbon::parse($row->data_prevista_consegna)->diffInDays($oggi);
                $fasiTot = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))->count();
                $fasiDone = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))->where('stato', '>=', 3)->count();
                $row->avanzamento = $fasiTot > 0 ? round(($fasiDone / $fasiTot) * 100) : 0;
                return $row;
            })
            ->sortByDesc('giorni_ritardo')
            ->values();

        // Top 5 operatori per fasi completate negli ultimi 7 giorni
        $topOperatori = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->join('operatori', 'operatori.id', '=', 'fase_operatore.operatore_id')
            ->where('ordine_fasi.stato', '>=', 3)
            ->where('fase_operatore.data_fine', '>=', $da)
            ->select('operatori.nome', 'operatori.cognome', DB::raw('COUNT(*) as fasi_completate'))
            ->groupBy('operatori.id', 'operatori.nome', 'operatori.cognome')
            ->orderByDesc('fasi_completate')
            ->limit(5)
            ->get();

        // Commesse completate nella settimana
        $commesseCompletate = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->where('ordine_fasi.stato', '>=', 3)
            ->where('fase_operatore.data_fine', '>=', $da)
            ->select('ordini.commessa', 'ordini.cliente_nome', 'ordini.descrizione')
            ->distinct()
            ->get()
            ->filter(function ($row) {
                $fasiNonComplete = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))
                    ->where('stato', '<', 3)->count();
                return $fasiNonComplete === 0;
            })
            ->values();

        return view('admin.report_produzione', [
            'dataInizio' => $da->format('d/m/Y'),
            'dataFine' => $oggi->format('d/m/Y'),
            'fasiCompletate' => $fasiCompletate7gg,
            'oreLavorate' => $oreLavorate,
            'commesseSpedite' => $commesseSpedite,
            'numCommesseInRitardo' => $commesseInRitardo->count(),
            'commesseInRitardo' => $commesseInRitardo,
            'topOperatori' => $topOperatori,
            'commesseCompletate' => $commesseCompletate,
        ]);
    }
}
