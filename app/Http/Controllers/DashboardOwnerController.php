<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdineFase;
use App\Models\Operatore;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\OrdiniImport;
use Illuminate\Support\Facades\DB;
use App\Models\Reparto;

class DashboardOwnerController extends Controller
{
    // Mostra tutte le fasi e ordini con calcolo ore e priorità
    public function index()
    {
        // Mappa fasi → avviamento e copieh
     $fasiInfo = [
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
        ];

        // Recupera tutte le fasi con le relazioni Eloquent
        $fasi = OrdineFase::with(['ordine', 'faseCatalogo', 'operatori' => function($q){
    $q->select('operatori.id','nome');
}])
->where('stato','!=',2)
->get()
->map(function ($fase) use ($fasiInfo) {
    // Quantità carta
    $qta_carta = $fase->ordine->qta_carta ?: 1;

    // Info fase
    $infoFase = $fasiInfo[$fase->fase] ?? ['avviamento'=>0,'copieh'=>0];

    // Ore necessarie
    $fase->ore = $infoFase['avviamento'] + ($infoFase['copieh'] / $qta_carta);

    // Giorni disponibili
    $giorni_disponibili = 0;
    if($fase->ordine->data_prevista_consegna && $fase->ordine->data_registrazione){
        $giorni_disponibili = (strtotime($fase->ordine->data_prevista_consegna) - strtotime($fase->ordine->data_registrazione)) / (60*60*24);
    }

    // Priorità
    $fase->priorita = round($giorni_disponibili + ($fase->ore / 24),2);

    // Data inizio della fase = primo operatore che ha avviato
    if($fase->operatori->isNotEmpty()){
        $primaData = $fase->operatori->sortBy('pivot.data_inizio')->first()->pivot->data_inizio;
        $fase->data_inizio = $primaData ? \Carbon\Carbon::parse($primaData)->format('d/m/Y H:i:s') : null;
    } else {
        $fase->data_inizio = null;
    }

    return $fase;
})
->sortBy('priorita');

$ultimoCodice = Operatore::orderBy('codice_operatore','desc')
->value('codice_operatore');

    $prossimoNumero=$ultimoCodice
    ? (int) substr($ultimoCodice, -3) + 1
    : 1;
$prossimoCodice='__'.str_pad($prossimoNumero,3,'0',STR_PAD_LEFT);
$reparti=Reparto::orderBy('nome')
->pluck('nome');
        return view('owner.dashboard', compact('fasi','prossimoNumero','prossimoCodice','reparti'));
    }

    // Aggiorna campi qta_prod e note
    public function aggiornaCampo(Request $request)
    {
        $request->validate([
            'fase_id' => 'required|exists:ordine_fasi,id',
            'campo' => 'required|string|in:qta_prod,note',
            'valore' => 'nullable'
        ]);

        $fase = OrdineFase::find($request->fase_id);
        if (!$fase) {
            return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
        }

        $fase->{$request->campo} = $request->valore;
        $fase->save();

        return response()->json(['success' => true]);
    }

public function aggiungiOperatore(Request $request)
{
    $request->validate([
        'nome' => 'required|string',
        'cognome' => 'required|string',
        'ruolo' => 'required|in:operatore,owner',
        'reparto' => 'required|array',
    ]);

    // 🔢 ultimo numero progressivo globale (ultime 3 cifre)
    $ultimoCodice = Operatore::orderBy('codice_operatore', 'desc')
        ->value('codice_operatore');

    $numero = $ultimoCodice
        ? (int) substr($ultimoCodice, -3)
        : 0;

    $numero++;

    // ✏️ normalizzazione nome
    $nome = ucfirst(strtolower($request->nome));
    $cognome = ucfirst(strtolower($request->cognome));

    // 🔠 iniziali
    $iniziali = strtoupper($nome[0] . $cognome[0]);

    // 🆔 codice finale
    $codice = $iniziali . str_pad($numero, 3, '0', STR_PAD_LEFT);

    // 🏭 reparti multipli
    $repartiPuliti= array_filter(array_unique($request->reparto));
    $repartoString = implode(',', $request->reparto);

    Operatore::create([
        'nome' => $nome,
        'cognome' => $cognome,
        'codice_operatore' => $codice,
        'ruolo' => $request->ruolo,
        'reparto' => $repartoString,
        'attivo' => 1,
    ]);

    return redirect()->back()->with('success', "Operatore $codice aggiunto correttamente");
}
    // Import ordini da file Excel
    public function importOrdini(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            Excel::import(new OrdiniImport, $request->file('file'));
            return redirect()->back()->with('success', 'Ordini importati correttamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Errore importazione: '.$e->getMessage());
        }
    }

   public function fasiTerminate()
{
  $fasiInfo = [
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
        ];
    $fasiTerminate = OrdineFase::with(['ordine','faseCatalogo','operatori'])
        ->where('stato',2)
        ->get()
        ->map(function ($fase) use ($fasiInfo) {

            // Quantità carta
            $qta_carta = $fase->ordine->qta_carta ?: 1;

            // Info fase
            $infoFase = $fasiInfo[$fase->fase] ?? ['avviamento'=>0,'copieh'=>0];

            // Ore necessarie
            $fase->ore = $infoFase['avviamento'] + ($infoFase['copieh'] / $qta_carta);

            // Giorni disponibili
            $giorni_disponibili = 0;
            if($fase->ordine->data_prevista_consegna && $fase->ordine->data_registrazione){
                $giorni_disponibili = (strtotime($fase->ordine->data_prevista_consegna) - strtotime($fase->ordine->data_registrazione)) / (60*60*24);
            }

            // Priorità
            $fase->priorita = round($giorni_disponibili + ($fase->ore / 24),2);

            // Data inizio = primo operatore
            if($fase->operatori->isNotEmpty()){
                $primaData = $fase->operatori->sortBy('pivot.data_inizio')->first()->pivot->data_inizio;
                $fase->data_inizio = $primaData ? \Carbon\Carbon::parse($primaData)->format('d/m/Y H:i:s') : null;
            } else {
                $fase->data_inizio = null;
            }

            return $fase;
        })
        ->sortBy('priorita');

    return view('owner.fasi_terminate', compact('fasiTerminate'));
}
}