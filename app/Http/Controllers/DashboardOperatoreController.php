<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdineFase;
use Illuminate\Support\Facades\DB;

class DashboardOperatoreController extends Controller
{
    public function index(Request $request)
    {
        // Operatore loggato
        $operatore = auth()->guard('operatore')->user();
        if (!$operatore) abort(403, 'Accesso negato');

        $reparti = array_map('trim', explode(',', $operatore->reparto));

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


       $fasiVisibili = OrdineFase::whereIn('ordine_fasi.reparto', $reparti)
    ->where('ordine_fasi.stato', '!=', 2)
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->select('ordine_fasi.*', 
             DB::raw('DATEDIFF(ordini.data_prevista_consegna, ordini.data_registrazione) AS giorni_disponibili'))
    ->with('ordine', 'faseCatalogo')
    ->get()
    ->map(function ($fase) use ($fasiInfo) {
        $qta_carta = $fase->ordine->qta_carta ?: 1;
        $infoFase = $fasiInfo[$fase->fase] ?? ['avviamento' => 0, 'copieh' => 0];

        // Ore necessarie
        $fase->ore = round($infoFase['avviamento'] + ($infoFase['copieh'] / $qta_carta), 2);

        // Giorni disponibili dal DB
        $giorni_disponibili = $fase->getAttribute('giorni_disponibili') ?: 0;

        // Priorità combinata
        $fase->priorita = round($giorni_disponibili + ($fase->ore / 24), 2);

        return $fase;
    })
    ->sortBy('priorita');

        return view('operatore.dashboard', compact('fasiVisibili', 'operatore'));
    }
}