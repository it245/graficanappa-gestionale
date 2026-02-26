<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrdineFase;
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

        // Mappa fasi → ore avviamento e copieh
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
            // Nuove fasi (valori da fasi simili)
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
            ];

        // Recupera le fasi visibili per i reparti dell'operatore
        $fasiVisibili = OrdineFase::where('stato', '<', 3)
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

                // Ordine fase dalla config
                $fasePriorita = config('fasi_priorita')[$fase->fase] ?? 500;

                // Priorità = giorni rimasti - ore/24 + ordineFase/10000
                $fase->priorita = round($giorni_rimasti - ($fase->ore / 24) + ($fasePriorita / 10000), 2);

                return $fase;
            })
            ->sortBy('priorita');

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

        return view('operatore.dashboard', compact('fasiVisibili', 'operatore', 'fasiPerReparto'));
    }
}