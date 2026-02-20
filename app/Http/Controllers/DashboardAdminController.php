<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Operatore;
use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\Reparto;
use App\Models\PausaOperatore;
use App\Models\PrinectAttivita;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\ReportDirezioneExport;
use App\Exports\ReportPrinectExport;
use Maatwebsite\Excel\Facades\Excel;

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
                        'fase_operatore.data_fine',
                        'fase_operatore.secondi_pausa'
                    )
                    ->get();

                $op->stat_fasi_completate = $fasi->filter(fn($f) => $f->stato >= 3)->count();
                $op->stat_fasi_in_corso = $fasi->where('stato', 2)->count();

                // Tempo totale lavorato (somma differenze data_inizio - data_fine dalla pivot, meno pause)
                $secTotale = 0;
                $fasiConTempo = 0;
                foreach ($fasi as $f) {
                    if ($f->data_inizio && $f->data_fine) {
                        $sec = Carbon::parse($f->data_fine)->diffInSeconds(Carbon::parse($f->data_inizio)) - ($f->secondi_pausa ?? 0);
                        $secTotale += max($sec, 0);
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
                        $secRecenti += Carbon::parse($f->data_fine)->diffInSeconds(Carbon::parse($f->data_inizio)) - ($f->secondi_pausa ?? 0);
                    }
                }
                $op->stat_sec_recenti = max($secRecenti, 0);

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

                // Ore effettive dalla pivot fase_operatore (sottraendo pause)
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

                    $totSecondiPausa = $fase->operatori->sum(fn($op) => $op->pivot->secondi_pausa ?? 0);

                    if ($dataInizio && $dataFine) {
                        $oreEffettive = max(Carbon::parse($dataFine)->diffInSeconds(Carbon::parse($dataInizio)) - $totSecondiPausa, 0) / 3600;
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
            ->select('data_inizio', 'data_fine', 'secondi_pausa')
            ->get();

        $secLavorati = 0;
        foreach ($pivotRecenti as $p) {
            $secLavorati += Carbon::parse($p->data_fine)->diffInSeconds(Carbon::parse($p->data_inizio)) - ($p->secondi_pausa ?? 0);
        }
        $oreLavorate = round(max($secLavorati, 0) / 3600, 1);

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

    public function cruscotto()
    {
        $oggi = Carbon::today();
        $da30gg = Carbon::now()->subDays(30);
        $da7gg = Carbon::now()->subDays(7);
        $prossimi7gg = Carbon::today()->addDays(7);

        // --- 1. Ordini attivi (commesse con almeno 1 fase non completata) ---
        $ordiniAttivi = Ordine::select('commessa')
            ->groupBy('commessa')
            ->get()
            ->filter(function ($row) {
                return OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))
                    ->where('stato', '<', 3)->exists();
            })->count();

        // --- 2. Completate ultimi 30gg ---
        $commesseCompletate30gg = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->where('ordine_fasi.stato', '>=', 3)
            ->where('fase_operatore.data_fine', '>=', $da30gg)
            ->select('ordini.commessa')
            ->distinct()
            ->get()
            ->filter(function ($row) {
                return OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))
                    ->where('stato', '<', 3)->count() === 0;
            })->count();

        // --- 3. Commesse in ritardo ---
        $commesseInRitardo = Ordine::where('data_prevista_consegna', '<', $oggi->toDateString())
            ->select('commessa', 'cliente_nome', 'data_prevista_consegna')
            ->groupBy('commessa', 'cliente_nome', 'data_prevista_consegna')
            ->get()
            ->filter(function ($row) {
                return OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))
                    ->where('stato', '<', 3)->count() > 0;
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

        // --- 4. Tasso puntualita ---
        // Commesse completate con data_prevista_consegna: ultima data_fine <= data_prevista_consegna
        $commesseConConsegna = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->where('ordine_fasi.stato', '>=', 3)
            ->where('fase_operatore.data_fine', '>=', $da30gg)
            ->whereNotNull('ordini.data_prevista_consegna')
            ->select('ordini.commessa', 'ordini.data_prevista_consegna', DB::raw('MAX(fase_operatore.data_fine) as ultima_fine'))
            ->groupBy('ordini.commessa', 'ordini.data_prevista_consegna')
            ->get()
            ->filter(function ($row) {
                return OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))
                    ->where('stato', '<', 3)->count() === 0;
            });

        $puntuali = $commesseConConsegna->filter(function ($row) {
            return $row->ultima_fine && Carbon::parse($row->ultima_fine)->lte(Carbon::parse($row->data_prevista_consegna)->endOfDay());
        })->count();

        $tassoPuntualita = $commesseConConsegna->count() > 0
            ? round(($puntuali / $commesseConConsegna->count()) * 100)
            : 0;

        // --- 5. Ore lavorate ultimi 30gg ---
        $pivotMese = DB::table('fase_operatore')
            ->where('data_fine', '>=', $da30gg)
            ->whereNotNull('data_inizio')
            ->whereNotNull('data_fine')
            ->select('data_inizio', 'data_fine', 'secondi_pausa')
            ->get();

        $secLavoratiMese = 0;
        foreach ($pivotMese as $p) {
            $secLavoratiMese += Carbon::parse($p->data_fine)->diffInSeconds(Carbon::parse($p->data_inizio)) - ($p->secondi_pausa ?? 0);
        }
        $oreLavorateMese = round(max($secLavoratiMese, 0) / 3600, 1);

        // --- 5b. Fasi completate ultimi 30gg ---
        $fasiCompletate30gg = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->where('ordine_fasi.stato', '>=', 3)
            ->where('fase_operatore.data_fine', '>=', $da30gg)
            ->count();

        // --- 6. Trend giornaliero (30gg) ---
        $trendRaw = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->where('ordine_fasi.stato', '>=', 3)
            ->where('fase_operatore.data_fine', '>=', $da30gg)
            ->whereNotNull('fase_operatore.data_inizio')
            ->whereNotNull('fase_operatore.data_fine')
            ->select(
                DB::raw('DATE(fase_operatore.data_fine) as giorno'),
                DB::raw('COUNT(*) as fasi'),
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, fase_operatore.data_inizio, fase_operatore.data_fine) - COALESCE(fase_operatore.secondi_pausa, 0)) as secondi')
            )
            ->groupBy('giorno')
            ->orderBy('giorno')
            ->get();

        $trendGiornaliero = $trendRaw->pluck('fasi', 'giorno')->toArray();
        $oreTrendGiornaliero = $trendRaw->mapWithKeys(function ($r) {
            return [$r->giorno => round($r->secondi / 3600, 1)];
        })->toArray();

        // --- 7. Carico reparti ---
        $reparti = Reparto::orderBy('nome')->get();
        $caricoReparti = $reparti->map(function ($rep) {
            $fasi = DB::table('ordine_fasi')
                ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
                ->where('fasi_catalogo.reparto_id', $rep->id)
                ->select('ordine_fasi.stato')
                ->get();

            return (object)[
                'nome' => $rep->nome,
                'attesa' => $fasi->whereIn('stato', [0, 1])->count(),
                'in_corso' => $fasi->where('stato', 2)->count(),
                'completate' => $fasi->where('stato', '>=', 3)->count(),
            ];
        })->filter(fn($r) => ($r->attesa + $r->in_corso + $r->completate) > 0)->values();

        // --- 8. Top clienti attivi ---
        $topClienti = Ordine::select('cliente_nome', DB::raw('COUNT(DISTINCT commessa) as totale'))
            ->whereNotNull('cliente_nome')
            ->where('cliente_nome', '!=', '')
            ->groupBy('cliente_nome')
            ->get()
            ->filter(function ($row) {
                // Solo clienti con commesse attive (almeno 1 fase incompleta)
                return Ordine::where('cliente_nome', $row->cliente_nome)
                    ->get()
                    ->pluck('commessa')
                    ->unique()
                    ->contains(function ($commessa) {
                        return OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
                            ->where('stato', '<', 3)->exists();
                    });
            })
            ->sortByDesc('totale')
            ->take(8)
            ->values();

        // --- 9. Motivi pausa ultimi 30gg ---
        $motiviPausa = PausaOperatore::where('data_ora', '>=', $da30gg)
            ->select('motivo', DB::raw('COUNT(*) as totale'))
            ->groupBy('motivo')
            ->orderByDesc('totale')
            ->get();

        // --- 10. Top 5 operatori ultimi 30gg ---
        $topOperatoriMese = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->join('operatori', 'operatori.id', '=', 'fase_operatore.operatore_id')
            ->where('ordine_fasi.stato', '>=', 3)
            ->where('fase_operatore.data_fine', '>=', $da30gg)
            ->select(
                'operatori.nome',
                'operatori.cognome',
                DB::raw('COUNT(*) as fasi_completate'),
                DB::raw('SUM(ordine_fasi.qta_prod) as qta_prodotta')
            )
            ->groupBy('operatori.id', 'operatori.nome', 'operatori.cognome')
            ->orderByDesc('fasi_completate')
            ->limit(5)
            ->get();

        // --- 11. Scadenze prossimi 7 giorni ---
        $scadenzeImminenti = Ordine::whereBetween('data_prevista_consegna', [$oggi->toDateString(), $prossimi7gg->toDateString()])
            ->select('commessa', 'cliente_nome', 'data_prevista_consegna')
            ->groupBy('commessa', 'cliente_nome', 'data_prevista_consegna')
            ->get()
            ->filter(function ($row) {
                return OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))
                    ->where('stato', '<', 3)->exists();
            })
            ->map(function ($row) use ($oggi) {
                $fasiTot = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))->count();
                $fasiDone = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))->where('stato', '>=', 3)->count();
                $row->avanzamento = $fasiTot > 0 ? round(($fasiDone / $fasiTot) * 100) : 0;
                $row->giorni_mancanti = $oggi->diffInDays(Carbon::parse($row->data_prevista_consegna));
                return $row;
            })
            ->sortBy('data_prevista_consegna')
            ->values();

        // --- 12. Prinect stats ultimi 7gg ---
        $prinectData = PrinectAttivita::where('start_time', '>=', $da7gg)->get();
        $prinectStats = (object)[
            'fogli_buoni' => $prinectData->sum('good_cycles'),
            'scarto' => $prinectData->sum('waste_cycles'),
            'percentuale_scarto' => ($prinectData->sum('good_cycles') + $prinectData->sum('waste_cycles')) > 0
                ? round(($prinectData->sum('waste_cycles') / ($prinectData->sum('good_cycles') + $prinectData->sum('waste_cycles'))) * 100, 1)
                : 0,
        ];

        return view('admin.cruscotto', compact(
            'ordiniAttivi', 'commesseCompletate30gg', 'commesseInRitardo',
            'tassoPuntualita', 'oreLavorateMese', 'fasiCompletate30gg',
            'caricoReparti', 'trendGiornaliero', 'oreTrendGiornaliero',
            'topClienti', 'topOperatoriMese', 'motiviPausa',
            'scadenzeImminenti', 'prinectStats'
        ));
    }

    // =====================================================================
    // REPORT DIREZIONE MULTI-PERIODO
    // =====================================================================

    private function calcolaKpiPeriodo($da, $a, $periodo = 'mese')
    {
        $oggi = Carbon::today();

        // --- Fasi completate ---
        $fasiCompletate = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->where('ordine_fasi.stato', '>=', 3)
            ->whereBetween('fase_operatore.data_fine', [$da, $a])
            ->count();

        // --- Ore lavorate ---
        $pivotRange = DB::table('fase_operatore')
            ->whereBetween('data_fine', [$da, $a])
            ->whereNotNull('data_inizio')
            ->whereNotNull('data_fine')
            ->select('data_inizio', 'data_fine', 'secondi_pausa')
            ->get();

        $secLavorati = 0;
        foreach ($pivotRange as $p) {
            $secLavorati += Carbon::parse($p->data_fine)->diffInSeconds(Carbon::parse($p->data_inizio)) - ($p->secondi_pausa ?? 0);
        }
        $oreLavorate = round(max($secLavorati, 0) / 3600, 1);

        // --- Commesse completate nel periodo ---
        $commesseCompletate = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->where('ordine_fasi.stato', '>=', 3)
            ->whereBetween('fase_operatore.data_fine', [$da, $a])
            ->select('ordini.commessa')
            ->distinct()
            ->get()
            ->filter(function ($row) {
                return OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))
                    ->where('stato', '<', 3)->count() === 0;
            });

        $numCommesseCompletate = $commesseCompletate->count();

        // --- Commesse in ritardo (data_prevista_consegna < oggi con fasi incomplete) ---
        $commesseInRitardo = Ordine::where('data_prevista_consegna', '<', $oggi->toDateString())
            ->select('commessa', 'cliente_nome', 'data_prevista_consegna')
            ->groupBy('commessa', 'cliente_nome', 'data_prevista_consegna')
            ->get()
            ->filter(function ($row) {
                return OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))
                    ->where('stato', '<', 3)->count() > 0;
            })
            ->map(function ($row) use ($oggi) {
                $row->giorni_ritardo = Carbon::parse($row->data_prevista_consegna)->diffInDays($oggi);
                $fasiTot = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))->count();
                $fasiDone = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))->where('stato', '>=', 3)->count();
                $row->avanzamento = $fasiTot > 0 ? round(($fasiDone / $fasiTot) * 100) : 0;
                $fasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))
                    ->where('stato', '<', 3)
                    ->with('faseCatalogo')
                    ->get();
                $row->fasi_mancanti = $fasi->map(fn($f) => $f->faseCatalogo->nome ?? $f->fase)->implode(', ');
                return $row;
            })
            ->sortByDesc('giorni_ritardo')
            ->values();

        // --- Tasso puntualita ---
        $commesseConConsegna = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->where('ordine_fasi.stato', '>=', 3)
            ->whereBetween('fase_operatore.data_fine', [$da, $a])
            ->whereNotNull('ordini.data_prevista_consegna')
            ->select('ordini.commessa', 'ordini.data_prevista_consegna', DB::raw('MAX(fase_operatore.data_fine) as ultima_fine'))
            ->groupBy('ordini.commessa', 'ordini.data_prevista_consegna')
            ->get()
            ->filter(function ($row) {
                return OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))
                    ->where('stato', '<', 3)->count() === 0;
            });

        $puntuali = $commesseConConsegna->filter(function ($row) {
            return $row->ultima_fine && Carbon::parse($row->ultima_fine)->lte(Carbon::parse($row->data_prevista_consegna)->endOfDay());
        })->count();

        $tassoPuntualita = $commesseConConsegna->count() > 0
            ? round(($puntuali / $commesseConConsegna->count()) * 100, 1)
            : 0;

        // --- Scarto Prinect ---
        $prinectData = PrinectAttivita::whereBetween('start_time', [$da, $a])->get();
        $goodCycles = $prinectData->sum('good_cycles');
        $wasteCycles = $prinectData->sum('waste_cycles');
        $scartoPercentuale = ($goodCycles + $wasteCycles) > 0
            ? round(($wasteCycles / ($goodCycles + $wasteCycles)) * 100, 1)
            : 0;

        // --- Colli di bottiglia reparti ---
        $reparti = Reparto::orderBy('nome')->get();
        $colliBottiglia = $reparti->map(function ($rep) use ($da, $a) {
            $fasi = DB::table('ordine_fasi')
                ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
                ->where('fasi_catalogo.reparto_id', $rep->id)
                ->select('ordine_fasi.stato')
                ->get();

            $completatePeriodo = DB::table('fase_operatore')
                ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
                ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
                ->where('fasi_catalogo.reparto_id', $rep->id)
                ->where('ordine_fasi.stato', '>=', 3)
                ->whereBetween('fase_operatore.data_fine', [$da, $a])
                ->count();

            // Tempo medio fase
            $tempiRep = DB::table('fase_operatore')
                ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
                ->join('fasi_catalogo', 'fasi_catalogo.id', '=', 'ordine_fasi.fase_catalogo_id')
                ->where('fasi_catalogo.reparto_id', $rep->id)
                ->where('ordine_fasi.stato', '>=', 3)
                ->whereBetween('fase_operatore.data_fine', [$da, $a])
                ->whereNotNull('fase_operatore.data_inizio')
                ->whereNotNull('fase_operatore.data_fine')
                ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, fase_operatore.data_inizio, fase_operatore.data_fine) - COALESCE(fase_operatore.secondi_pausa, 0)) as media_sec'))
                ->value('media_sec');

            $coda = $fasi->whereIn('stato', [0, 1])->count();
            $inCorso = $fasi->where('stato', 2)->count();
            $indiceBottleneck = $completatePeriodo > 0 ? round($coda / $completatePeriodo, 2) : ($coda > 0 ? 999 : 0);

            return (object)[
                'nome' => $rep->nome,
                'coda' => $coda,
                'in_corso' => $inCorso,
                'completate_periodo' => $completatePeriodo,
                'tempo_medio_sec' => round($tempiRep ?? 0),
                'indice_bottleneck' => $indiceBottleneck,
            ];
        })->filter(fn($r) => ($r->coda + $r->in_corso + $r->completate_periodo) > 0)
          ->sortByDesc('indice_bottleneck')
          ->values();

        // --- Trend produzione (granularita adattiva) ---
        if (in_array($periodo, ['anno'])) {
            $groupExprFasi = DB::raw("DATE_FORMAT(fase_operatore.data_fine, '%Y-%m') as periodo");
            $granularita = 'mese';
        } elseif (in_array($periodo, ['trimestre', 'semestre'])) {
            $groupExprFasi = DB::raw("CONCAT(YEAR(fase_operatore.data_fine), '-W', LPAD(WEEK(fase_operatore.data_fine, 1), 2, '0')) as periodo");
            $granularita = 'settimana';
        } else {
            $groupExprFasi = DB::raw("DATE(fase_operatore.data_fine) as periodo");
            $granularita = 'giorno';
        }

        $trendRaw = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->where('ordine_fasi.stato', '>=', 3)
            ->whereBetween('fase_operatore.data_fine', [$da, $a])
            ->whereNotNull('fase_operatore.data_inizio')
            ->whereNotNull('fase_operatore.data_fine')
            ->select(
                $groupExprFasi,
                DB::raw('COUNT(*) as fasi'),
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, fase_operatore.data_inizio, fase_operatore.data_fine) - COALESCE(fase_operatore.secondi_pausa, 0)) as secondi')
            )
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();

        $trendGiornaliero = $trendRaw->pluck('fasi', 'periodo')->toArray();
        $oreTrendGiornaliero = $trendRaw->mapWithKeys(fn($r) => [$r->periodo => round($r->secondi / 3600, 1)])->toArray();

        // --- Performance operatori ---
        $operatoriPerf = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->join('operatori', 'operatori.id', '=', 'fase_operatore.operatore_id')
            ->where('ordine_fasi.stato', '>=', 3)
            ->whereBetween('fase_operatore.data_fine', [$da, $a])
            ->whereNotNull('fase_operatore.data_inizio')
            ->whereNotNull('fase_operatore.data_fine')
            ->select(
                'operatori.id',
                'operatori.nome',
                'operatori.cognome',
                DB::raw('COUNT(*) as fasi_completate'),
                DB::raw('SUM(ordine_fasi.qta_prod) as qta_prodotta'),
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, fase_operatore.data_inizio, fase_operatore.data_fine) - COALESCE(fase_operatore.secondi_pausa, 0)) as sec_totali')
            )
            ->groupBy('operatori.id', 'operatori.nome', 'operatori.cognome')
            ->orderByDesc('fasi_completate')
            ->get()
            ->map(function ($op) use ($da, $a) {
                $op->ore_lavorate = round($op->sec_totali / 3600, 1);
                $op->tempo_medio_sec = $op->fasi_completate > 0 ? round($op->sec_totali / $op->fasi_completate) : 0;
                $giorni = max(Carbon::parse($da)->diffInDays(Carbon::parse($a)), 1);
                $op->fasi_giorno = round($op->fasi_completate / $giorni, 1);
                // Reparti operatore
                $repOp = DB::table('operatore_reparto')
                    ->join('reparti', 'reparti.id', '=', 'operatore_reparto.reparto_id')
                    ->where('operatore_reparto.operatore_id', $op->id)
                    ->pluck('reparti.nome');
                $op->reparti = $repOp->implode(', ');
                return $op;
            });

        // --- Pause ---
        $motiviPausa = PausaOperatore::whereBetween('data_ora', [$da, $a])
            ->select('motivo', DB::raw('COUNT(*) as totale'))
            ->groupBy('motivo')
            ->orderByDesc('totale')
            ->get();

        $totalePause = $motiviPausa->sum('totale');
        $motiviPausa->each(function ($m) use ($totalePause) {
            $m->percentuale = $totalePause > 0 ? round(($m->totale / $totalePause) * 100, 1) : 0;
        });

        // --- Trend scarto Prinect (granularita adattiva) ---
        if (in_array($periodo, ['anno'])) {
            $groupExprPrinect = DB::raw("DATE_FORMAT(start_time, '%Y-%m') as periodo");
        } elseif (in_array($periodo, ['trimestre', 'semestre'])) {
            $groupExprPrinect = DB::raw("CONCAT(YEAR(start_time), '-W', LPAD(WEEK(start_time, 1), 2, '0')) as periodo");
        } else {
            $groupExprPrinect = DB::raw("DATE(start_time) as periodo");
        }

        $prinectTrend = DB::table('prinect_attivita')
            ->whereBetween('start_time', [$da, $a])
            ->select(
                $groupExprPrinect,
                DB::raw('SUM(good_cycles) as good'),
                DB::raw('SUM(waste_cycles) as waste')
            )
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get()
            ->map(function ($r) {
                $totale = $r->good + $r->waste;
                $r->scarto_pct = $totale > 0 ? round(($r->waste / $totale) * 100, 1) : 0;
                return $r;
            });

        // --- Top 5 commesse per scarto Prinect ---
        $topScartoCommesse = DB::table('prinect_attivita')
            ->whereBetween('start_time', [$da, $a])
            ->whereNotNull('commessa_gestionale')
            ->where('commessa_gestionale', '!=', '')
            ->select(
                'commessa_gestionale',
                DB::raw('SUM(good_cycles) as good'),
                DB::raw('SUM(waste_cycles) as waste')
            )
            ->groupBy('commessa_gestionale')
            ->get()
            ->map(function ($r) {
                $totale = $r->good + $r->waste;
                $r->scarto_pct = $totale > 0 ? round(($r->waste / $totale) * 100, 1) : 0;
                return $r;
            })
            ->sortByDesc('scarto_pct')
            ->take(5)
            ->values();

        // --- Top clienti ---
        $topClienti = DB::table('fase_operatore')
            ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
            ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->whereBetween('fase_operatore.data_fine', [$da, $a])
            ->whereNotNull('ordini.cliente_nome')
            ->where('ordini.cliente_nome', '!=', '')
            ->select('ordini.cliente_nome', DB::raw('COUNT(DISTINCT ordini.commessa) as commesse'))
            ->groupBy('ordini.cliente_nome')
            ->orderByDesc('commesse')
            ->limit(10)
            ->get();

        // --- Dettaglio commesse completate ---
        $dettaglioCompletate = $commesseCompletate->map(function ($row) use ($da, $a) {
            $ordine = Ordine::where('commessa', $row->commessa)->first();
            $fasiTot = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $row->commessa))->count();
            // Tempo totale
            $secTotali = DB::table('fase_operatore')
                ->join('ordine_fasi', 'ordine_fasi.id', '=', 'fase_operatore.fase_id')
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->where('ordini.commessa', $row->commessa)
                ->whereNotNull('fase_operatore.data_inizio')
                ->whereNotNull('fase_operatore.data_fine')
                ->sum(DB::raw('TIMESTAMPDIFF(SECOND, fase_operatore.data_inizio, fase_operatore.data_fine) - COALESCE(fase_operatore.secondi_pausa, 0)'));

            return (object)[
                'commessa' => $row->commessa,
                'cliente' => $ordine->cliente_nome ?? '-',
                'descrizione' => $ordine->descrizione ?? '-',
                'fasi_totali' => $fasiTot,
                'ore_totali' => round($secTotali / 3600, 1),
                'data_consegna' => $ordine->data_prevista_consegna ?? null,
            ];
        })->values();

        return (object)[
            'fasiCompletate' => $fasiCompletate,
            'oreLavorate' => $oreLavorate,
            'numCommesseCompletate' => $numCommesseCompletate,
            'numCommesseInRitardo' => $commesseInRitardo->count(),
            'commesseInRitardo' => $commesseInRitardo,
            'tassoPuntualita' => $tassoPuntualita,
            'scartoPercentuale' => $scartoPercentuale,
            'goodCycles' => $goodCycles,
            'wasteCycles' => $wasteCycles,
            'colliBottiglia' => $colliBottiglia,
            'trendGiornaliero' => $trendGiornaliero,
            'oreTrendGiornaliero' => $oreTrendGiornaliero,
            'operatoriPerf' => $operatoriPerf,
            'motiviPausa' => $motiviPausa,
            'totalePause' => $totalePause,
            'prinectTrend' => $prinectTrend,
            'topScartoCommesse' => $topScartoCommesse,
            'topClienti' => $topClienti,
            'dettaglioCompletate' => $dettaglioCompletate,
            'granularita' => $granularita,
        ];
    }

    public function reportDirezione(Request $request)
    {
        $periodo = $request->get('periodo', 'mese');
        $oggi = Carbon::today();

        switch ($periodo) {
            case 'settimana':
                $da = $oggi->copy()->subWeek();
                $a = $oggi->copy()->endOfDay();
                $daPrev = $oggi->copy()->subWeeks(2);
                $aPrev = $oggi->copy()->subWeek();
                break;
            case 'trimestre':
                $da = $oggi->copy()->subMonths(3);
                $a = $oggi->copy()->endOfDay();
                $daPrev = $oggi->copy()->subMonths(6);
                $aPrev = $oggi->copy()->subMonths(3);
                break;
            case 'semestre':
                $da = $oggi->copy()->subMonths(6);
                $a = $oggi->copy()->endOfDay();
                $daPrev = $oggi->copy()->subMonths(12);
                $aPrev = $oggi->copy()->subMonths(6);
                break;
            case 'anno':
                $da = $oggi->copy()->subYear();
                $a = $oggi->copy()->endOfDay();
                $daPrev = $oggi->copy()->subYears(2);
                $aPrev = $oggi->copy()->subYear();
                break;
            default: // mese
                $periodo = 'mese';
                $da = $oggi->copy()->subMonth();
                $a = $oggi->copy()->endOfDay();
                $daPrev = $oggi->copy()->subMonths(2);
                $aPrev = $oggi->copy()->subMonth();
                break;
        }

        $labelPeriodo = $da->format('d M Y') . ' - ' . $a->format('d M Y');
        $labelPrecedente = $daPrev->format('d M Y') . ' - ' . $aPrev->format('d M Y');

        $kpi = $this->calcolaKpiPeriodo($da, $a, $periodo);
        $kpiPrev = $this->calcolaKpiPeriodo($daPrev, $aPrev, $periodo);

        // Calcolo delta %
        $delta = (object)[];
        $delta->fasiCompletate = $kpiPrev->fasiCompletate > 0
            ? round((($kpi->fasiCompletate - $kpiPrev->fasiCompletate) / $kpiPrev->fasiCompletate) * 100, 1) : null;
        $delta->oreLavorate = $kpiPrev->oreLavorate > 0
            ? round((($kpi->oreLavorate - $kpiPrev->oreLavorate) / $kpiPrev->oreLavorate) * 100, 1) : null;
        $delta->commesseCompletate = $kpiPrev->numCommesseCompletate > 0
            ? round((($kpi->numCommesseCompletate - $kpiPrev->numCommesseCompletate) / $kpiPrev->numCommesseCompletate) * 100, 1) : null;
        $delta->commesseInRitardo = $kpiPrev->numCommesseInRitardo > 0
            ? round((($kpi->numCommesseInRitardo - $kpiPrev->numCommesseInRitardo) / $kpiPrev->numCommesseInRitardo) * 100, 1) : null;
        // Per tasso puntualita e scarto: delta in punti percentuali
        $delta->tassoPuntualita = round($kpi->tassoPuntualita - $kpiPrev->tassoPuntualita, 1);
        $delta->scartoPercentuale = round($kpi->scartoPercentuale - $kpiPrev->scartoPercentuale, 1);

        return view('admin.report_direzione', compact(
            'periodo', 'labelPeriodo', 'labelPrecedente',
            'kpi', 'kpiPrev', 'delta'
        ));
    }

    public function reportDirezioneExcel(Request $request)
    {
        $periodo = $request->get('periodo', 'mese');
        $oggi = Carbon::today();

        switch ($periodo) {
            case 'settimana':
                $da = $oggi->copy()->subWeek();
                $a = $oggi->copy()->endOfDay();
                $daPrev = $oggi->copy()->subWeeks(2);
                $aPrev = $oggi->copy()->subWeek();
                break;
            case 'trimestre':
                $da = $oggi->copy()->subMonths(3);
                $a = $oggi->copy()->endOfDay();
                $daPrev = $oggi->copy()->subMonths(6);
                $aPrev = $oggi->copy()->subMonths(3);
                break;
            case 'semestre':
                $da = $oggi->copy()->subMonths(6);
                $a = $oggi->copy()->endOfDay();
                $daPrev = $oggi->copy()->subMonths(12);
                $aPrev = $oggi->copy()->subMonths(6);
                break;
            case 'anno':
                $da = $oggi->copy()->subYear();
                $a = $oggi->copy()->endOfDay();
                $daPrev = $oggi->copy()->subYears(2);
                $aPrev = $oggi->copy()->subYear();
                break;
            default:
                $da = $oggi->copy()->subMonth();
                $a = $oggi->copy()->endOfDay();
                $daPrev = $oggi->copy()->subMonths(2);
                $aPrev = $oggi->copy()->subMonth();
                break;
        }

        $kpi = $this->calcolaKpiPeriodo($da, $a, $periodo);
        $kpiPrev = $this->calcolaKpiPeriodo($daPrev, $aPrev, $periodo);

        $labelPeriodo = $da->format('d-m-Y') . '_' . $a->format('d-m-Y');

        return Excel::download(
            new ReportDirezioneExport($kpi, $kpiPrev, $periodo),
            "ReportDirezione_{$periodo}_{$labelPeriodo}.xlsx"
        );
    }

    // =====================================================================
    // REPORT PRINECT MULTI-PERIODO
    // =====================================================================

    private function calcolaKpiPrinect($da, $a, $periodo = 'mese')
    {
        $attivita = PrinectAttivita::whereBetween('start_time', [$da, $a])->get();

        $goodCycles = $attivita->sum('good_cycles');
        $wasteCycles = $attivita->sum('waste_cycles');
        $totaleFogli = $goodCycles + $wasteCycles;
        $scartoPerc = $totaleFogli > 0 ? round(($wasteCycles / $totaleFogli) * 100, 1) : 0;
        $nAttivita = $attivita->count();

        // Tempo avviamento vs produzione
        $secAvviamento = 0;
        $secProduzione = 0;
        foreach ($attivita as $att) {
            if (!$att->start_time || !$att->end_time) continue;
            $diff = $att->start_time->diffInSeconds($att->end_time);
            if ($att->activity_name === 'Avviamento') {
                $secAvviamento += $diff;
            } else {
                $secProduzione += $diff;
            }
        }
        $secTotali = $secAvviamento + $secProduzione;
        $oreTotali = round($secTotali / 3600, 1);
        $oreAvviamento = round($secAvviamento / 3600, 1);
        $oreProduzione = round($secProduzione / 3600, 1);
        $rapportoAvvProd = $secTotali > 0 ? round(($secAvviamento / $secTotali) * 100, 1) : 0;

        // Commesse lavorate
        $nCommesse = $attivita->pluck('commessa_gestionale')->filter()->unique()->count();

        // OEE: giorni lavorativi effettivi (lun-sab) nel periodo
        $velocitaNominale = 18000;
        $giorniLav = 0;
        $cursor = Carbon::parse($da)->copy();
        $fine = Carbon::parse($a);
        while ($cursor->lte($fine)) {
            if ($cursor->dayOfWeek !== Carbon::SUNDAY) $giorniLav++;
            $cursor->addDay();
        }
        $giorniLav = max($giorniLav, 1);
        $oreTurnoPianificate = 23 * $giorniLav;
        $disponibilita = $oreTurnoPianificate > 0 ? min($secTotali / ($oreTurnoPianificate * 3600), 1) : 0;
        $performance = ($secProduzione > 0 && $velocitaNominale > 0) ? min($totaleFogli / (($secProduzione / 3600) * $velocitaNominale), 1) : 0;
        $qualita = $totaleFogli > 0 ? $goodCycles / $totaleFogli : 0;
        $oee = round($disponibilita * $performance * $qualita * 100, 1);
        $oeeDisp = round($disponibilita * 100, 1);
        $oeePerf = round($performance * 100, 1);
        $oeeQual = round($qualita * 100, 1);

        // Granularita trend: giorno per sett/mese, settimana per trimestre/semestre, mese per anno
        if (in_array($periodo, ['anno'])) {
            $groupExpr = DB::raw("DATE_FORMAT(start_time, '%Y-%m') as periodo");
            $granularita = 'mese';
        } elseif (in_array($periodo, ['trimestre', 'semestre'])) {
            $groupExpr = DB::raw("CONCAT(YEAR(start_time), '-W', LPAD(WEEK(start_time, 1), 2, '0')) as periodo");
            $granularita = 'settimana';
        } else {
            $groupExpr = DB::raw("DATE(start_time) as periodo");
            $granularita = 'giorno';
        }

        // Trend produzione
        $trendRaw = DB::table('prinect_attivita')
            ->whereBetween('start_time', [$da, $a])
            ->select(
                $groupExpr,
                DB::raw('SUM(good_cycles) as good'),
                DB::raw('SUM(waste_cycles) as waste'),
                DB::raw('COUNT(*) as n_attivita')
            )
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();

        $trendGiornaliero = $trendRaw->map(function ($r) {
            $totale = $r->good + $r->waste;
            return (object)[
                'giorno' => $r->periodo,
                'good' => (int) $r->good,
                'waste' => (int) $r->waste,
                'totale' => $totale,
                'scarto_pct' => $totale > 0 ? round(($r->waste / $totale) * 100, 1) : 0,
                'n_attivita' => (int) $r->n_attivita,
            ];
        });

        // Trend tempo avviamento vs produzione
        if (in_array($periodo, ['anno'])) {
            $groupExpr2 = DB::raw("DATE_FORMAT(start_time, '%Y-%m') as periodo");
        } elseif (in_array($periodo, ['trimestre', 'semestre'])) {
            $groupExpr2 = DB::raw("CONCAT(YEAR(start_time), '-W', LPAD(WEEK(start_time, 1), 2, '0')) as periodo");
        } else {
            $groupExpr2 = DB::raw("DATE(start_time) as periodo");
        }

        $trendTempi = DB::table('prinect_attivita')
            ->whereBetween('start_time', [$da, $a])
            ->whereNotNull('end_time')
            ->select(
                $groupExpr2,
                DB::raw("SUM(CASE WHEN activity_name = 'Avviamento' THEN TIMESTAMPDIFF(SECOND, start_time, end_time) ELSE 0 END) as sec_avv"),
                DB::raw("SUM(CASE WHEN activity_name != 'Avviamento' THEN TIMESTAMPDIFF(SECOND, start_time, end_time) ELSE 0 END) as sec_prod")
            )
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();

        // Per operatore
        $perOperatore = collect();
        foreach ($attivita as $att) {
            $nome = $att->operatore_prinect ?: 'N/D';
            if (!$perOperatore->has($nome)) {
                $perOperatore[$nome] = (object)[
                    'nome' => $nome,
                    'buoni' => 0, 'scarto' => 0,
                    'sec_avv' => 0, 'sec_prod' => 0, 'n_attivita' => 0,
                ];
            }
            $perOperatore[$nome]->buoni += $att->good_cycles;
            $perOperatore[$nome]->scarto += $att->waste_cycles;
            $diff = ($att->start_time && $att->end_time) ? $att->start_time->diffInSeconds($att->end_time) : 0;
            if ($att->activity_name === 'Avviamento') {
                $perOperatore[$nome]->sec_avv += $diff;
            } else {
                $perOperatore[$nome]->sec_prod += $diff;
            }
            $perOperatore[$nome]->n_attivita++;
        }
        $perOperatore = $perOperatore->sortByDesc('buoni')->values();

        // Per commessa (top 15)
        $perCommessa = $attivita->whereNotNull('commessa_gestionale')
            ->where('commessa_gestionale', '!=', '')
            ->groupBy('commessa_gestionale')
            ->map(function ($gruppo) {
                $secAvv = 0; $secProd = 0;
                foreach ($gruppo as $a) {
                    if (!$a->start_time || !$a->end_time) continue;
                    $diff = $a->start_time->diffInSeconds($a->end_time);
                    if ($a->activity_name === 'Avviamento') { $secAvv += $diff; } else { $secProd += $diff; }
                }
                $buoni = $gruppo->sum('good_cycles');
                $scarto = $gruppo->sum('waste_cycles');
                $totale = $buoni + $scarto;
                return (object)[
                    'commessa' => $gruppo->first()->commessa_gestionale,
                    'job_name' => $gruppo->first()->prinect_job_name ?? '-',
                    'buoni' => $buoni,
                    'scarto' => $scarto,
                    'scarto_pct' => $totale > 0 ? round(($scarto / $totale) * 100, 1) : 0,
                    'sec_avv' => $secAvv,
                    'sec_prod' => $secProd,
                    'n_attivita' => $gruppo->count(),
                ];
            })
            ->sortByDesc('buoni')
            ->take(15)
            ->values();

        // Top 5 commesse per scarto %
        $topScarto = $attivita->whereNotNull('commessa_gestionale')
            ->where('commessa_gestionale', '!=', '')
            ->groupBy('commessa_gestionale')
            ->map(function ($gruppo) {
                $buoni = $gruppo->sum('good_cycles');
                $scarto = $gruppo->sum('waste_cycles');
                $totale = $buoni + $scarto;
                return (object)[
                    'commessa' => $gruppo->first()->commessa_gestionale,
                    'job_name' => $gruppo->first()->prinect_job_name ?? '-',
                    'buoni' => $buoni,
                    'scarto' => $scarto,
                    'scarto_pct' => $totale > 0 ? round(($scarto / $totale) * 100, 1) : 0,
                ];
            })
            ->filter(fn($c) => ($c->buoni + $c->scarto) > 50)
            ->sortByDesc('scarto_pct')
            ->take(5)
            ->values();

        return (object)[
            'goodCycles' => $goodCycles,
            'wasteCycles' => $wasteCycles,
            'totaleFogli' => $totaleFogli,
            'scartoPerc' => $scartoPerc,
            'nAttivita' => $nAttivita,
            'nCommesse' => $nCommesse,
            'oreAvviamento' => $oreAvviamento,
            'oreProduzione' => $oreProduzione,
            'oreTotali' => $oreTotali,
            'rapportoAvvProd' => $rapportoAvvProd,
            'oee' => $oee,
            'oeeDisp' => $oeeDisp,
            'oeePerf' => $oeePerf,
            'oeeQual' => $oeeQual,
            'trendGiornaliero' => $trendGiornaliero,
            'trendTempi' => $trendTempi,
            'granularita' => $granularita,
            'perOperatore' => $perOperatore,
            'perCommessa' => $perCommessa,
            'topScarto' => $topScarto,
        ];
    }

    private function parsePeriodoPrinect($periodo)
    {
        $oggi = Carbon::today();
        switch ($periodo) {
            case 'settimana':
                return [$oggi->copy()->subWeek(), $oggi->copy()->endOfDay(), $oggi->copy()->subWeeks(2), $oggi->copy()->subWeek()];
            case 'trimestre':
                return [$oggi->copy()->subMonths(3), $oggi->copy()->endOfDay(), $oggi->copy()->subMonths(6), $oggi->copy()->subMonths(3)];
            case 'semestre':
                return [$oggi->copy()->subMonths(6), $oggi->copy()->endOfDay(), $oggi->copy()->subMonths(12), $oggi->copy()->subMonths(6)];
            case 'anno':
                return [$oggi->copy()->subYear(), $oggi->copy()->endOfDay(), $oggi->copy()->subYears(2), $oggi->copy()->subYear()];
            default: // mese
                return [$oggi->copy()->subMonth(), $oggi->copy()->endOfDay(), $oggi->copy()->subMonths(2), $oggi->copy()->subMonth()];
        }
    }

    public function reportPrinect(Request $request)
    {
        $periodo = $request->get('periodo', 'mese');
        if (!in_array($periodo, ['settimana','mese','trimestre','semestre','anno'])) $periodo = 'mese';

        [$da, $a, $daPrev, $aPrev] = $this->parsePeriodoPrinect($periodo);

        $labelPeriodo = $da->format('d M Y') . ' - ' . $a->format('d M Y');
        $labelPrecedente = $daPrev->format('d M Y') . ' - ' . $aPrev->format('d M Y');

        $kpi = $this->calcolaKpiPrinect($da, $a, $periodo);
        $kpiPrev = $this->calcolaKpiPrinect($daPrev, $aPrev, $periodo);

        // Delta
        $delta = (object)[];
        $delta->goodCycles = $kpiPrev->goodCycles > 0 ? round((($kpi->goodCycles - $kpiPrev->goodCycles) / $kpiPrev->goodCycles) * 100, 1) : null;
        $delta->wasteCycles = $kpiPrev->wasteCycles > 0 ? round((($kpi->wasteCycles - $kpiPrev->wasteCycles) / $kpiPrev->wasteCycles) * 100, 1) : null;
        $delta->scartoPerc = round($kpi->scartoPerc - $kpiPrev->scartoPerc, 1);
        $delta->oreTotali = $kpiPrev->oreTotali > 0 ? round((($kpi->oreTotali - $kpiPrev->oreTotali) / $kpiPrev->oreTotali) * 100, 1) : null;
        $delta->nCommesse = $kpiPrev->nCommesse > 0 ? round((($kpi->nCommesse - $kpiPrev->nCommesse) / $kpiPrev->nCommesse) * 100, 1) : null;
        $delta->oee = round($kpi->oee - $kpiPrev->oee, 1);

        return view('admin.report_prinect', compact(
            'periodo', 'labelPeriodo', 'labelPrecedente',
            'kpi', 'kpiPrev', 'delta'
        ));
    }

    public function reportPrinectExcel(Request $request)
    {
        $periodo = $request->get('periodo', 'mese');
        if (!in_array($periodo, ['settimana','mese','trimestre','semestre','anno'])) $periodo = 'mese';

        [$da, $a, $daPrev, $aPrev] = $this->parsePeriodoPrinect($periodo);

        $kpi = $this->calcolaKpiPrinect($da, $a, $periodo);
        $kpiPrev = $this->calcolaKpiPrinect($daPrev, $aPrev, $periodo);

        $labelPeriodo = $da->format('d-m-Y') . '_' . $a->format('d-m-Y');

        return Excel::download(
            new ReportPrinectExport($kpi, $kpiPrev, $periodo),
            "ReportPrinect_{$periodo}_{$labelPeriodo}.xlsx"
        );
    }
}
