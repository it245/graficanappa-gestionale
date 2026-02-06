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
use App\Models\FasiCatalogo;
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

// Trova l'ultimo numero progressivo già usato
$ultimoNumero = Operatore::selectRaw('CAST(RIGHT(codice_operatore, 3) AS UNSIGNED) as numero')
    ->orderByDesc('numero')
    ->value('numero');

$prossimoNumero = $ultimoNumero ? $ultimoNumero + 1 : 1;

// Codice temporaneo con iniziali placeholder "__"
$prossimoCodice = '__' . str_pad($prossimoNumero, 3, '0', STR_PAD_LEFT);
$reparti=Reparto::orderBy('nome')
->pluck('nome','id');
$fasiCatalogo = fasiCatalogo::all();
        return view('owner.dashboard', compact('fasi','prossimoNumero','prossimoCodice','reparti','fasiCatalogo'));
    }


public function aggiornaCampo(Request $request)
{
    $request->validate([
        'fase_id' => 'required|exists:ordine_fasi,id',
        'campo' => 'required|string',
        'valore' => 'nullable'
    ]);

    $fase = OrdineFase::find($request->fase_id);
    if (!$fase) {
        return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);
    }

    $campo = $request->campo;
    $valore = $request->valore;

    $campiPermessi = [
        'cliente_nome', 'cod_art', 'descrizione', 'qta_richiesta', 'um', 'priorita',
        'data_registrazione', 'data_prevista_consegna', 'cod_carta', 'carta', 'qta_carta', 'UM_carta',
        'qta_prod', 'note', 'data_inizio', 'data_fine', 'stato'
    ];

    if (!in_array($campo, $campiPermessi)) {
        return response()->json(['success' => false, 'messaggio' => 'Campo non modificabile']);
    }

    // Se è una data, converti in formato MySQL
    if (in_array($campo, ['data_registrazione', 'data_prevista_consegna', 'data_inizio', 'data_fine'])) {
        try {
            $valore = $valore ? \Carbon\Carbon::createFromFormat('d/m/Y', $valore)->format('Y-m-d') : null;
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'messaggio' => 'Formato data non valido']);
        }
    }

    // Aggiorna il campo
    $fase->{$campo} = $valore;
    $fase->save();

    return response()->json(['success' => true]);
}

public function aggiungiOperatore(Request $request)
{
    // ✅ Validazione input
    $request->validate([
        'nome' => 'required|string',
        'cognome' => 'required|string',
        'ruolo' => 'required|in:operatore,owner',
        'reparto_principale' => 'required|exists:reparti,id',
        'reparto_secondario' => 'nullable|exists:reparti,id',
    ]);

    // 🔢 Numero progressivo globale per il codice operatore
    $ultimoNumero = Operatore::selectRaw(
            'CAST(RIGHT(codice_operatore, 3) AS UNSIGNED) as numero'
        )
        ->orderByDesc('numero')
        ->value('numero');

    $numero = $ultimoNumero ? $ultimoNumero + 1 : 1;

    // 🔠 Formattazione nome e cognome
    $nome = ucfirst(strtolower($request->nome));
    $cognome = ucfirst(strtolower($request->cognome));
    $iniziali = strtoupper($nome[0] . $cognome[0]);
    $codice = $iniziali . str_pad($numero, 3, '0', STR_PAD_LEFT);

    // ✅ Creazione operatore
    $operatore = Operatore::create([
        'nome' => $nome,
        'cognome' => $cognome,
        'codice_operatore' => $codice,
        'ruolo' => $request->ruolo,
        'attivo' => 1,
    ]);

    // 🔗 Associazione reparti (pivot)
    $reparti = array_filter([
        $request->reparto_principale,
        $request->reparto_secondario
    ]);
    $operatore->reparti()->sync($reparti);

    // 🟢 Aggiorna il reparto principale nella tabella operatori
    $operatore->reparto_id = $request->reparto_principale;
    $operatore->save();

    return redirect()->back()
        ->with('success', "Operatore $codice aggiunto correttamente");
}
    // Import ordini da file Excel
   public function importOrdini(Request $request)
{
    // Controllo ruolo (puoi togliere il dd in produzione)
    \Log::info('Utente import ordini ruolo: ' . session('operatore_ruolo'));

    // Validazione file
    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls'
    ]);

    $file = $request->file('file');

    if (!$file->isValid()) {
        \Log::error('File upload non valido.');
        return redirect()->back()->with('error', 'File non valido.');
    }

    try {
        // Log prima dell'import
        \Log::info('Inizio import ordini da file: ' . $file->getClientOriginalName());

        // Import Excel
        Excel::import(new \App\Imports\OrdiniImport, $file);

        \Log::info('Import completato correttamente');

        return redirect()->back()->with('success', 'Ordini importati correttamente.');

    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
        // Errori di validazione Excel
        $failures = $e->failures();
        foreach ($failures as $failure) {
            \Log::error("Riga {$failure->row()}: " . implode(', ', $failure->errors()));
        }
        return redirect()->back()->with('error', 'Errore di validazione Excel. Controlla il log.');
    } catch (\Exception $e) {
        \Log::error('Errore generico import Excel: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Errore importazione: ' . $e->getMessage());
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