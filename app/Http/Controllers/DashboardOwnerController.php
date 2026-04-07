<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\Operatore;
use App\Models\Reparto;
use App\Models\FasiCatalogo;
use App\Models\DdtSpedizione;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Helpers\DescrizioneParser;
use App\Services\FaseStatoService;
use App\Services\OndaSyncService;
use App\Http\Services\PrinectService;
use App\Http\Services\PrinectSyncService;
use App\Http\Services\ExcelSyncService;

class DashboardOwnerController extends Controller
{
    private function isReadonly(): bool
    {
        return session('operatore_ruolo') === 'owner_readonly'
            || (request()->attributes->get('operatore_ruolo') === 'owner_readonly');
    }

    private function denyIfReadonly()
    {
        if ($this->isReadonly()) {
            if (request()->expectsJson()) {
                abort(403, 'Accesso in sola lettura');
            }
            return back()->with('error', 'Accesso in sola lettura — non puoi modificare i dati.');
        }
        return null;
    }

    /**
     * Panoramica commesse attive raggruppate per reparto
     */
    public function repartiOverview(Request $request)
    {
        // Ordine ciclo produttivo
        $ordineReparti = [
            'prestampa', 'stampa offset', 'digitale', 'stampa a caldo',
            'plastificazione', 'fustella', 'fustella piana', 'fustella cilindrica',
            'tagliacarte', 'finestratura', 'piegaincolla', 'legatoria',
            'finitura digitale', 'generico', 'produzione', 'magazzino',
            'spedizione', 'esterno',
        ];
        $reparti = Reparto::all()->sortBy(function ($r) use ($ordineReparti) {
            $pos = array_search(strtolower($r->nome), $ordineReparti);
            return $pos !== false ? $pos : 999;
        });

        $data = [];
        foreach ($reparti as $reparto) {
            // Fasi in corso (stato 2) in questo reparto, non esterne
            $fasi = OrdineFase::query()
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
                ->where('fasi_catalogo.reparto_id', $reparto->id)
                ->where('ordine_fasi.stato', 2)
                ->where(fn($q) => $q->where('ordine_fasi.esterno', false)->orWhereNull('ordine_fasi.esterno'))
                ->whereNull('ordine_fasi.deleted_at')
                ->select([
                    'ordini.commessa',
                    'ordini.cliente_nome',
                    'ordini.descrizione',
                    'ordini.data_prevista_consegna',
                    'ordini.qta_richiesta',
                    'ordine_fasi.id as fase_id',
                    'ordine_fasi.fase',
                    'ordine_fasi.stato as fase_stato',
                    'ordine_fasi.priorita',
                    'ordine_fasi.priorita_manuale',
                    'ordine_fasi.qta_prod',
                    'ordine_fasi.operatore_id',
                    'fasi_catalogo.nome as fase_catalogo_nome',
                ])
                ->orderBy('ordine_fasi.priorita')
                ->get();

            // Raggruppa per commessa
            $fasiInfo = $this->fasiInfo;
            $commesse = $fasi->groupBy('commessa')->map(function ($group) use ($fasiInfo) {
                $first = $group->first();
                $stati = $group->pluck('fase_stato');
                // Calcola ore previste per commessa in questo reparto
                $orePreviste = $group->sum(function ($f) use ($fasiInfo) {
                    $info = $fasiInfo[$f->fase] ?? ['avviamento' => 0.5, 'copieh' => 1000];
                    $copieh = $info['copieh'] ?: 1000;
                    return $info['avviamento'] + (($f->qta_richiesta ?: 0) / $copieh);
                });
                return (object)[
                    'commessa'    => $first->commessa,
                    'cliente'     => $first->cliente_nome ?: '-',
                    'descrizione' => $first->descrizione ?: '-',
                    'consegna'    => $first->data_prevista_consegna,
                    'qta'         => $first->qta_richiesta,
                    'priorita'    => $group->min('priorita'),
                    'n_fasi'      => $group->count(),
                    'n_inizio'    => $stati->filter(fn($s) => $s == 1)->count(),
                    'n_terminato' => $stati->filter(fn($s) => $s == 2)->count(),
                    'n_attesa'    => $stati->filter(fn($s) => $s == 0)->count(),
                    'fasi'        => $group->pluck('fase_catalogo_nome')->unique()->values()->all(),
                    'ore_previste' => round($orePreviste, 1),
                ];
            })->sortBy('priorita')->values();

            $data[] = (object)[
                'reparto'  => $reparto,
                'commesse' => $commesse,
                'totale'   => $commesse->count(),
            ];
        }

        // Rimuovi reparti senza commesse attive
        $data = collect($data)->filter(fn($r) => $r->totale > 0)->values();

        $totReparti = $reparti->count();
        $opToken = request()->query('op_token') ?? request()->attributes->get('op_token');

        return view('owner.reparti_overview', compact('data', 'opToken', 'totReparti'));
    }

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
        // Nuove fasi (valori da fasi simili)
        '4graph' => ['avviamento' => 0.5, 'copieh' => 100],           // come Allest.Manuale (esterno)
        'stampalaminaoro' => ['avviamento' => 1, 'copieh' => 2200],   // come STAMPACALDOJOH
        'STAMPALAMINAORO' => ['avviamento' => 1, 'copieh' => 2200],
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

        // Fasi "est" (esterno) — avviamento 72, copieh come fase interna corrispondente
        'est STAMPACALDOJOH' => ['avviamento' => 72, 'copieh' => 2200],
        'est FUSTSTELG33.44' => ['avviamento' => 72, 'copieh' => 1500],
        'est FUSTBOBST75X106' => ['avviamento' => 72, 'copieh' => 3000],
        'STAMPA.ESTERNA' => ['avviamento' => 72, 'copieh' => 1000],

        // Fasi EXT* (esterno) — avviamento 72, copieh come fase interna corrispondente
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

        // Altre fasi nuove
        'DEKIA-semplice' => ['avviamento' => 0.5, 'copieh' => 200],    // come DEKIA-Difficile
        'STAMPASECCO' => ['avviamento' => 0.5, 'copieh' => 2000],      // come RILIEVOASECCOJOH
        'STAMPACALDO04' => ['avviamento' => 1, 'copieh' => 2200],      // come STAMPACALDOJOH
        'STAMPACALDOBR' => ['avviamento' => 1, 'copieh' => 2200],      // come STAMPACALDOJOH
        'STAMPAINDIGOBIANCO' => ['avviamento' => 0.5, 'copieh' => 1000], // come STAMPAINDIGO
    ];

public function calcolaOreEPriorita($fase)
    {
        $qta_carta = $fase->ordine->qta_carta ?: 0;
        $infoFase = $this->fasiInfo[$fase->fase] ?? ['avviamento' => 0.5, 'copieh' => 1000];
        $copieh = $infoFase['copieh'] ?: 1000;

        // ore = avviamento + qtaCarta / copieh (pezzi da fare / pezzi all'ora)
        $fase->ore = $infoFase['avviamento'] + ($qta_carta / $copieh);

        // Se priorità manuale (impostata da Excel o utente), non ricalcolare
        if ($fase->priorita_manuale) {
            return $fase;
        }

        // Se priorità manuale (range -901 a -999), non sovrascrivere
        $currentPriorita = $fase->priorita ?? 0;
        if ($currentPriorita <= -901 && $currentPriorita >= -999) {
            return $fase;
        }

        // giorni rimasti = dataPrevConsegna - OGGI (negativo se scaduta)
        $giorni_rimasti = 0;
        if ($fase->ordine->data_prevista_consegna) {
            $oggi = Carbon::today();
            $consegna = Carbon::parse($fase->ordine->data_prevista_consegna)->startOfDay();
            $giorni_rimasti = ($consegna->timestamp - $oggi->timestamp) / 86400;
        }

        // Ordine fase dalla config (es. STAMPA=10, PLASTIFICA=20, BRT=999)
        $fasePriorita = config('fasi_priorita')[$fase->fase] ?? 500;

        // Priorità = giorni rimasti - ore/24 + ordineFase/100
        $prioritaCalcolata = round($giorni_rimasti - ($fase->ore / 24) + ($fasePriorita / 100), 2);

        $fase->priorita = $prioritaCalcolata;
        return $fase;
    }

    public function index(Request $request, PrinectService $prinect, PrinectSyncService $syncService)
    {
        // Sync Excel: importa eventuali modifiche dal file
        ExcelSyncService::syncIfModified();

        // Sync live Prinect: aggiorna fasi stampa da attivita di oggi
        try {
            $deviceId = env('PRINECT_DEVICE_XL106_ID', '4001');
            $oggi = Carbon::today()->format('Y-m-d\TH:i:sP');
            $ora = Carbon::now()->format('Y-m-d\TH:i:sP');
            $apiOggi = $prinect->getDeviceActivity($deviceId, $oggi, $ora);
            $rawOggi = collect($apiOggi['activities'] ?? [])
                ->filter(fn($a) => !empty($a['id']))
                ->values()
                ->toArray();
            $syncService->sincronizzaDaLive($rawOggi);
        } catch (\Exception $e) {
            // Prinect non disponibile, continua senza sync
        }

        $fasi = OrdineFase::with(['ordine.fasi.faseCatalogo', 'faseCatalogo.reparto', 'operatori' => fn($q) => $q->select('operatori.id', 'nome')])
            ->where('stato', '<', 4)
            ->whereHas('ordine')
            ->get()
            ->map(function ($fase) {
                $fase = $this->calcolaOreEPriorita($fase);

                $fase->reparto_nome = $fase->faseCatalogo->reparto->nome ?? '-';

                if ($fase->operatori->isNotEmpty()) {
                    $primaData = $fase->operatori->sortBy('pivot.data_inizio')->first()->pivot->data_inizio;
                    $fase->data_inizio = $primaData ? Carbon::parse($primaData)->format('Y-m-d H:i:s') : null;
                } else {
                    $fase->data_inizio = null;
                }

                return $fase;
            })
            ->sortBy('priorita');

        $reparti = Reparto::orderBy('nome')->pluck('nome', 'id');
        $fasiCatalogo = FasiCatalogo::all();

        // Report spedizioni di oggi (stato 4 = consegnato)
        $repartoSpedizione = Reparto::where('nome', 'spedizione')->first();
        $spedizioniOggi = collect();
        if ($repartoSpedizione) {
            $spedizioniOggi = OrdineFase::where('stato', 4)
                ->whereDate('data_fine', Carbon::today())
                ->whereHas('faseCatalogo', function ($q) use ($repartoSpedizione) {
                    $q->where('reparto_id', $repartoSpedizione->id);
                })
                ->with(['ordine', 'faseCatalogo', 'operatori'])
                ->get()
                ->sortByDesc('data_fine');
        }

        // KPI giornalieri
        $oggi = Carbon::today();

        // Fasi completate oggi: dalla pivot O dal campo ordine_fasi.data_fine
        $fasiCompletateOggi = OrdineFase::where('stato', '>=', 3)
            ->where(function ($q) use ($oggi) {
                $q->whereHas('operatori', fn($q2) => $q2->whereDate('fase_operatore.data_fine', $oggi))
                  ->orWhereDate('data_fine', $oggi);
            })
            ->count();

        // Ore lavorate oggi: dalla pivot operatore
        // Solo record con data_inizio E data_fine nello stesso giorno, OPPURE data_fine oggi
        $pivotOggi = DB::table('fase_operatore')
            ->whereDate('data_fine', $oggi)
            ->whereNotNull('data_inizio')
            ->select('data_inizio', 'data_fine', 'secondi_pausa')
            ->get();
        $orePivot = $pivotOggi->sum(function ($row) use ($oggi) {
            $inizio = Carbon::parse($row->data_inizio);
            $fine = Carbon::parse($row->data_fine);
            $secLordo = abs($fine->diffInSeconds($inizio));
            $secPausa = min($row->secondi_pausa ?? 0, $secLordo);
            $secNetto = max($secLordo - $secPausa, 0);
            // Se le ore nette sono > 24h, qualcosa non torna — limita a 24h
            return min($secNetto / 3600, 24);
        });

        // Ore da fasi Prinect terminate oggi (usa tempo_avviamento + esecuzione, NON date)
        $orePrinect = OrdineFase::where('stato', '>=', 3)
            ->whereDate('data_fine', $oggi)
            ->where(function ($q) {
                $q->where('tempo_avviamento_sec', '>', 0)
                  ->orWhere('tempo_esecuzione_sec', '>', 0);
            })
            ->whereDoesntHave('operatori', fn($q) => $q->whereDate('fase_operatore.data_fine', $oggi))
            ->sum(DB::raw('COALESCE(tempo_avviamento_sec, 0) + COALESCE(tempo_esecuzione_sec, 0)')) / 3600;

        $oreLavorateOggi = round($orePivot + $orePrinect, 1);

        // Breakdown ore per operatore (per modal dettaglio)
        $orePerOperatoreOggi = DB::table('fase_operatore')
            ->join('operatori', 'fase_operatore.operatore_id', '=', 'operatori.id')
            ->whereDate('fase_operatore.data_fine', $oggi)
            ->whereNotNull('fase_operatore.data_inizio')
            ->select(
                'operatori.nome', 'operatori.cognome',
                DB::raw('COUNT(*) as fasi_count'),
                DB::raw('SUM(TIMESTAMPDIFF(SECOND, fase_operatore.data_inizio, fase_operatore.data_fine)) as sec_lordo'),
                DB::raw('SUM(COALESCE(fase_operatore.secondi_pausa, 0)) as sec_pausa')
            )
            ->groupBy('operatori.nome', 'operatori.cognome')
            ->orderByDesc('sec_lordo')
            ->get()
            ->map(function ($op) {
                $secNetto = max($op->sec_lordo - $op->sec_pausa, 0);
                $op->ore_nette = round(min($secNetto / 3600, $op->fasi_count * 24), 1);
                return $op;
            });

        $commesseSpediteOggi = OrdineFase::where('stato', 4)
            ->where(function ($q) use ($oggi) {
                $q->whereHas('operatori', fn($q2) => $q2->whereDate('fase_operatore.data_fine', $oggi))
                  ->orWhereDate('data_fine', $oggi);
            })
            ->with('ordine')
            ->get()
            ->pluck('ordine.commessa')
            ->unique()
            ->count();

        $fasiAttive = OrdineFase::where('stato', 2)->count();

        // Storico consegne (ultimi 30 giorni, escluso oggi)
        $storicoConsegne = collect();
        if ($repartoSpedizione) {
            $storicoConsegne = OrdineFase::where('stato', 4)
                ->whereDate('data_fine', '<', Carbon::today())
                ->whereDate('data_fine', '>=', Carbon::today()->subDays(30))
                ->whereHas('faseCatalogo', function ($q) use ($repartoSpedizione) {
                    $q->where('reparto_id', $repartoSpedizione->id);
                })
                ->with(['ordine', 'faseCatalogo', 'operatori'])
                ->orderByDesc('data_fine')
                ->get();
        }

        // Spedizioni BRT: dalla tabella ddt_spedizioni (supporta più DDT per commessa)
        // Escludi DDT consegnati da più di 5 giorni
        $limiteConsegna = Carbon::today()->subDays(5)->format('Y-m-d');
        $spedizioniBRT = DdtSpedizione::with('ordine:id,descrizione')
            ->where('vettore', 'LIKE', '%BRT%')
            ->where(function ($q) use ($limiteConsegna) {
                $q->whereNull('brt_data_consegna')
                  ->orWhere('brt_data_consegna', '>=', $limiteConsegna);
            })
            ->orderByDesc('onda_id_doc')
            ->get()
            ->groupBy('numero_ddt');

        // Progresso fasi per commessa (tutte le fasi, incluse stato >= 4)
        $tutteFasiCommesse = OrdineFase::with('ordine')
            ->whereHas('ordine')
            ->get()
            ->groupBy(fn($f) => $f->ordine->commessa ?? '');
        $progressoCommesse = [];
        foreach ($tutteFasiCommesse as $comm => $fasiComm) {
            $totale = $fasiComm->count();
            $terminate = $fasiComm->where('stato', '>=', 3)->count();
            $avviate = $fasiComm->where('stato', 2)->count();
            $progressoCommesse[$comm] = [
                'totale' => $totale,
                'terminate' => $terminate,
                'avviate' => $avviate,
                'percentuale' => $totale > 0 ? round(($terminate / $totale) * 100) : 0,
            ];
        }

        // Sync Excel: aggiorna il file con i dati freschi
        ExcelSyncService::exportToExcel();

        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();
        $isReadonly = $this->isReadonly();

        // Riempimento macchine — ottimizzato: 2 query batch invece di 18
        $repartiRiemp = [
            ['nome' => 'XL 106', 'reparti' => ['stampa offset']],
            ['nome' => 'BOBST', 'reparti' => ['fustella piana']],
            ['nome' => 'JOH Caldo', 'reparti' => ['stampa a caldo']],
            ['nome' => 'Plastificatrice', 'reparti' => ['plastificazione']],
            ['nome' => 'Piegaincolla', 'reparti' => ['piegaincolla']],
            ['nome' => 'Finestratrice', 'reparti' => ['finestratura']],
            ['nome' => 'Fust. Cilindrica', 'reparti' => ['fustella cilindrica']],
            ['nome' => 'Digitale', 'reparti' => ['digitale', 'finitura digitale']],
            ['nome' => 'Tagliacarte', 'reparti' => ['tagliacarte']],
        ];
        $fasiOreConfig = config('fasi_ore');

        // 1 query: pre-carica tutti i reparti
        $tuttiReparti = Reparto::all()->keyBy('nome');

        // 2 query batch: tutte le fasi stato 0 e 1 con reparto, in una sola query per stato
        $fasiPerReparto = [];
        foreach ([0, 1] as $stato) {
            $fasiRiemp = DB::table('ordine_fasi')
                ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
                ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
                ->join('reparti', 'fasi_catalogo.reparto_id', '=', 'reparti.id')
                ->where('ordine_fasi.stato', $stato)
                ->whereNull('ordine_fasi.deleted_at')
                ->where(fn($q) => $q->where('ordine_fasi.esterno', 0)->orWhereNull('ordine_fasi.esterno'))
                ->where(fn($q) => $q->whereNull('ordine_fasi.note')->orWhere('ordine_fasi.note', 'NOT LIKE', '%Inviato a:%'))
                ->select('ordine_fasi.fase', 'ordini.qta_carta', 'reparti.nome as reparto_nome')
                ->get();
            foreach ($fasiRiemp as $f) {
                $fasiPerReparto[$f->reparto_nome][$stato][] = $f;
            }
        }

        $riempimento = [];
        foreach ($repartiRiemp as $ru) {
            $ore0 = 0; $ore1 = 0; $cnt0 = 0; $cnt1 = 0;
            foreach ($ru['reparti'] as $repNome) {
                foreach ([0, 1] as $stato) {
                    $fasiStato = $fasiPerReparto[$repNome][$stato] ?? [];
                    foreach ($fasiStato as $f) {
                        $info = $fasiOreConfig[$f->fase] ?? null;
                        $ore = $info ? $info['avviamento'] + (($f->qta_carta ?? 0) / ($info['copieh'] ?: 1000)) : 0.5;
                        if ($stato === 0) { $ore0 += $ore; $cnt0++; }
                        else { $ore1 += $ore; $cnt1++; }
                    }
                }
            }
            $riempimento[] = [
                'nome' => $ru['nome'],
                'ore_0' => round($ore0, 1), 'fasi_0' => $cnt0,
                'ore_1' => round($ore1, 1), 'fasi_1' => $cnt1,
                'ore_totali' => round($ore0 + $ore1, 1),
            ];
        }
        usort($riempimento, fn($a, $b) => $b['ore_totali'] <=> $a['ore_totali']);

        return view('owner.dashboard', compact(
            'fasi', 'reparti', 'fasiCatalogo', 'spedizioniOggi', 'storicoConsegne',
            'fasiCompletateOggi', 'oreLavorateOggi', 'orePerOperatoreOggi', 'commesseSpediteOggi', 'fasiAttive', 'spedizioniBRT', 'operatore', 'isReadonly',
            'progressoCommesse', 'riempimento'
        ));
    }

    public function aggiornaCampo(Request $request)
    {
        if ($deny = $this->denyIfReadonly()) return $deny;
        $campiFase = ['qta_prod', 'qta_fase', 'note', 'stato', 'data_inizio', 'data_fine', 'ore', 'priorita', 'fase', 'esterno'];
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
            if (in_array(trim($valore), ['-', ''])) $valore = null;
            $formati = ['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y'];
            $parsed = false;
            foreach ($formati as $fmt) {
                try {
                    $valore = $valore ? Carbon::createFromFormat($fmt, trim($valore))->format('Y-m-d H:i:s') : null;
                    $parsed = true;
                    break;
                } catch (\Exception $e) {}
            }
            if (!$parsed && $valore) {
                return response()->json(['success' => false, 'messaggio' => 'Formato data non valido'], 422);
            }
        }

        $campiNumerici = ['qta_prod', 'qta_carta', 'qta_richiesta', 'priorita', 'ore'];
        if (in_array($campo, $campiNumerici)) {
            $valore = $valore !== null ? (float)str_replace(',', '.', $valore) : 0;
        }

        if ($campo === 'fase') {
            // Aggiorna nome fase + fase_catalogo_id
            $nomeNuovo = trim($valore) ?: '-';
            $fase->fase = $nomeNuovo;
            if ($nomeNuovo !== '-') {
                $faseCat = FasiCatalogo::where('nome', $nomeNuovo)->first();
                if ($faseCat) {
                    $fase->fase_catalogo_id = $faseCat->id;
                }
            }
            $fase->save();
        } elseif ($campo === 'reparto') {
            // Aggiorna reparto del FasiCatalogo o crea nuovo FasiCatalogo
            $nomeReparto = trim($valore) ?: 'generico';
            $reparto = Reparto::firstOrCreate(['nome' => $nomeReparto]);
            $faseNome = $fase->fase ?: '-';
            $faseCat = FasiCatalogo::updateOrCreate(
                ['nome' => $faseNome],
                ['reparto_id' => $reparto->id]
            );
            $fase->fase_catalogo_id = $faseCat->id;
            $fase->save();
        } elseif (in_array($campo, $campiFase)) {
            $fase->{$campo} = $valore;

            // Se priorità cambiata manualmente, segnala come manuale
            if ($campo === 'priorita') {
                $fase->priorita_manuale = true;
            }

            $fase->save();

            // Se aggiornata qta_prod, controlla completamento automatico
            if ($campo === 'qta_prod') {
                FaseStatoService::controllaCompletamento($fase->id);
            }

            // Se stato cambiato, gestisci data_fine, flag esterno e ricalcola
            if ($campo === 'stato') {
                $statoNum = (int) $valore;
                // Terminata: imposta data_fine se mancante
                if ($statoNum == 3 && !$fase->data_fine) {
                    $fase->data_fine = now()->format('Y-m-d H:i:s');
                    $fase->save();
                }
                // Recuperata (riportata a 2): azzera data_fine
                if ($statoNum == 2) {
                    $fase->data_fine = null;
                    $fase->save();
                }
                // Se riportata a 0 o 1, pulisci: flag esterno, data_fine, nota "Inviato a:"
                if ($statoNum <= 1) {
                    $fase->data_fine = null;
                    if ($fase->esterno) {
                        $fase->esterno = false;
                    }
                    if ($fase->note && preg_match('/Inviato a:/i', $fase->note)) {
                        $fase->note = preg_replace('/,?\s*Inviato a:.*$/i', '', $fase->note);
                        $fase->note = trim($fase->note) ?: null;
                    }
                    $fase->save();
                }
                FaseStatoService::ricalcolaCommessa($fase->ordine->commessa);
            }

            // Se note contengono "esterno", "lavorato esternamente" o "Inviato a:", segna come esterno
            if ($campo === 'note' && preg_match('/\b(lavorato esternamente|esterno)\b|Inviato a:/i', $valore ?? '')) {
                if (!$fase->esterno) {
                    $fase->esterno = true;
                    $fase->save();
                }
            }

            // Se note contengono "Inviato a:", notifica la spedizione + avvia la fase
            if ($campo === 'note' && preg_match('/Inviato a:\s*(.+)/i', $valore ?? '', $mInv)) {
                $fornitore = trim($mInv[1]);
                $commessa = $fase->ordine->commessa ?? '';
                $faseNome = $fase->faseCatalogo->nome_display ?? $fase->fase ?? '';
                $descrizione = mb_substr($fase->ordine->descrizione ?? '', 0, 60);

                // Segna come esterno (stato 5 = in lavorazione esterna)
                if (in_array((int) $fase->stato, [0, 1])) {
                    $fase->stato = 5;
                    $fase->data_inizio = $fase->data_inizio ?? now();
                    $fase->esterno = true;
                    $fase->save();
                }

                // Notifica la spedizione
                DB::table('notifiche_spedizione')->insert([
                    'tipo' => 'invio_esterno',
                    'commessa' => $commessa,
                    'fase' => $faseNome,
                    'fornitore' => $fornitore,
                    'messaggio' => "{$faseNome} commessa {$commessa} inviata a {$fornitore} — {$descrizione}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
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

                app()->terminating(fn() => ExcelSyncService::exportToExcel());
                return response()->json(['success' => true, 'reload' => true]);
            }
        } else {
            return response()->json(['success' => false, 'messaggio' => 'Campo non aggiornabile'], 422);
        }

        app()->terminating(fn() => ExcelSyncService::exportToExcel());
        return response()->json(['success' => true]);
    }

    public function aggiungiRiga(Request $request)
    {
        if ($deny = $this->denyIfReadonly()) return $deny;
        $faseCatalogo = $request->fase_catalogo_id
            ? FasiCatalogo::with('reparto')->find($request->fase_catalogo_id)
            : null;

        // Auto-append anno (-26, -27, ecc.) se mancante
        $commessa = trim($request->commessa);
        $suffissoAnno = '-' . date('y');
        if (!preg_match('/-\d{2}$/', $commessa)) {
            $commessa .= $suffissoAnno;
        }

        $ordine = Ordine::create([
            'commessa' => $commessa,
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
            'manuale' => true,
        ]);

        return redirect()->back()->with('success', 'Riga aggiunta correttamente.');
    }

    public function importOrdini(Request $request)
    {
        if ($deny = $this->denyIfReadonly()) return $deny;
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

    public function fasiTerminate(Request $request)
{
    $soloOggi = $request->boolean('oggi');
    $oggi = Carbon::today();

    $query = OrdineFase::with([
            'ordine.reparto',
            'faseCatalogo.reparto',
            'operatori'
        ])
        ->whereIn('stato', [3, 4]);

    if ($soloOggi) {
        $query->where(function ($q) use ($oggi) {
            $q->whereHas('operatori', fn($q2) => $q2->whereDate('fase_operatore.data_fine', $oggi))
              ->orWhereDate('data_fine', $oggi);
        });
    }

    $fasiTerminate = $query->get()
        ->map(function ($fase) {

            // Calcolo ore e priorità
            $fase = $this->calcolaOreEPriorita($fase);

            // Reparto: da faseCatalogo (piu affidabile) oppure da ordine
            $fase->reparto_nome = $fase->faseCatalogo->reparto->nome
                ?? $fase->ordine->reparto->nome
                ?? '-';

            // DATA INIZIO: dalla pivot operatore, fallback dal campo ordine_fasi
            $dataInizioOriginale = $fase->getAttributes()['data_inizio'] ?? null;
            $carbonInizio = null;
            $fase->data_inizio = null;

            if ($fase->operatori->isNotEmpty()) {
                $primaDataInizio = $fase->operatori
                    ->whereNotNull('pivot.data_inizio')
                    ->sortBy('pivot.data_inizio')
                    ->first()?->pivot->data_inizio;

                if ($primaDataInizio) {
                    $carbonInizio = Carbon::parse($primaDataInizio);
                    $fase->data_inizio = $carbonInizio->format('Y-m-d H:i:s');
                }
            }

            // Fallback: data_inizio dal campo ordine_fasi (impostato da Prinect sync)
            if (!$fase->data_inizio && $dataInizioOriginale) {
                $carbonInizio = Carbon::parse($dataInizioOriginale);
                $fase->data_inizio = $carbonInizio->format('Y-m-d H:i:s');
            }

            // DATA FINE: dalla pivot operatore, fallback dal campo ordine_fasi
            $dataFineOriginale = $fase->getAttributes()['data_fine'] ?? null;
            $carbonFine = null;
            $fase->data_fine = null;

            if ($fase->operatori->isNotEmpty()) {
                $primaDataFine = $fase->operatori
                    ->whereNotNull('pivot.data_fine')
                    ->sortBy('pivot.data_fine')
                    ->first()?->pivot->data_fine;

                if ($primaDataFine) {
                    $carbonFine = Carbon::parse($primaDataFine);
                    $fase->data_fine = $carbonFine->format('Y-m-d H:i:s');
                }
            }

            // Fallback: data_fine dal campo ordine_fasi (impostato da Prinect sync)
            if (!$fase->data_fine && $dataFineOriginale) {
                $carbonFine = Carbon::parse($dataFineOriginale);
                $fase->data_fine = $carbonFine->format('Y-m-d H:i:s');
            }

            // Calcolo ore lavorate e pausa
            $fase->secondi_pausa_totale = $fase->operatori->sum(fn($op) => $op->pivot->secondi_pausa ?? 0);
            $fase->secondi_lordo = 0;
            if ($carbonInizio && $carbonFine) {
                $fase->secondi_lordo = abs($carbonFine->getTimestamp() - $carbonInizio->getTimestamp());
            }

            return $fase;
        })
        ->sortBy('priorita');

    return view('owner.fasi_terminate', compact('fasiTerminate', 'soloOggi'));
}

    public function dettaglioCommessa($commessa, PrinectService $prinect, PrinectSyncService $syncService)
    {
        // Sync live: aggiorna fasi stampa da attivita Prinect di oggi
        try {
            $deviceId = env('PRINECT_DEVICE_XL106_ID', '4001');
            $oggi = Carbon::today()->format('Y-m-d\TH:i:sP');
            $ora = Carbon::now()->format('Y-m-d\TH:i:sP');
            $apiOggi = $prinect->getDeviceActivity($deviceId, $oggi, $ora);
            $rawOggi = collect($apiOggi['activities'] ?? [])
                ->filter(fn($a) => !empty($a['id']))
                ->values()
                ->toArray();
            $syncService->sincronizzaDaLive($rawOggi);
        } catch (\Exception $e) {
            // Prinect non disponibile, continua senza sync
        }

        $ordini = Ordine::where('commessa', $commessa)->with('fasi.faseCatalogo.reparto', 'fasi.operatori')->get();
        if ($ordini->isEmpty()) abort(404, 'Commessa non trovata');

        $fasi = $ordini->flatMap(function ($ordine) {
            return $ordine->fasi->map(function ($fase) use ($ordine) {
                $fase->ordine = $ordine;
                $fase->reparto_nome = $fase->faseCatalogo->reparto->nome ?? '-';
                $fase = $this->calcolaOreEPriorita($fase);
                return $fase;
            });
        })->sortBy(function ($fase) {
            $nome = $fase->faseCatalogo->nome ?? '';
            $ordine = config('fasi_ordine');
            return $ordine[$nome] ?? $ordine[strtolower($nome)] ?? 999;
        });

        // Anteprima foglio di stampa da Prinect API
        $preview = null;
        $jobId = ltrim(substr($commessa, 0, 7), '0');
        if ($jobId && is_numeric($jobId)) {
            try {
                $wsData = $prinect->getJobWorksteps($jobId);
                foreach ($wsData['worksteps'] ?? [] as $ws) {
                    if (isset($ws['types']) && in_array('ConventionalPrinting', $ws['types'])) {
                        $prevData = $prinect->getWorkstepPreview($jobId, $ws['id']);
                        $previews = $prevData['previews'] ?? [];
                        if (!empty($previews)) {
                            $preview = $previews[0];
                        }
                        break;
                    }
                }
            } catch (\Exception $e) {
                // Prinect non disponibile, nessuna anteprima
            }
        }

        $ordine = $ordini->first();
        return view('owner.dettaglio_commessa', compact('commessa', 'fasi', 'preview', 'ordine', 'ordini'));
    }

    public function eliminaFase(Request $request)
    {
        if ($deny = $this->denyIfReadonly()) return $deny;
        $fase = OrdineFase::find($request->fase_id);
        if (!$fase) return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);

        $fase->operatori()->detach();
        $fase->delete();

        return response()->json(['success' => true]);
    }

    public function aggiornaStato(Request $request)
    {
        if ($deny = $this->denyIfReadonly()) return $deny;
        $fase = OrdineFase::find($request->fase_id);
        if (!$fase) return response()->json(['success' => false, 'messaggio' => 'Fase non trovata']);

        $nuovoStato = (int) $request->stato;
        $fase->stato = $nuovoStato;

        if ($nuovoStato == 3 && !$fase->data_fine) {
            $fase->data_fine = now()->format('Y-m-d H:i:s');
            $fase->terminata_manualmente = true;
        }

        // Se riportata a stato 2 (recuperata), azzera data_fine e flag manuale
        if ($nuovoStato == 2) {
            $fase->data_fine = null;
            $fase->terminata_manualmente = false;
        }

        // Se riportata a 0 o 1, pulisci tutto: flag esterno, data_fine, nota "Inviato a:"
        if ($nuovoStato <= 1) {
            $fase->data_fine = null;
            if ($fase->esterno) {
                $fase->esterno = false;
            }
            // Rimuovi "Inviato a:" dalla nota
            if ($fase->note && preg_match('/Inviato a:/i', $fase->note)) {
                $fase->note = preg_replace('/,?\s*Inviato a:.*$/i', '', $fase->note);
                $fase->note = trim($fase->note) ?: null;
            }
        }

        $fase->save();

        // Ricalcola stati delle fasi successive
        FaseStatoService::ricalcolaStati($fase->ordine_id);

        app()->terminating(fn() => ExcelSyncService::exportToExcel());
        return response()->json(['success' => true, 'messaggio' => 'Stato aggiornato']);
    }

    public function ricalcolaStati()
    {
        if ($deny = $this->denyIfReadonly()) return $deny;
        FaseStatoService::ricalcolaTutti();
        return response()->json(['success' => true, 'messaggio' => 'Stati ricalcolati']);
    }

    public function syncOnda()
    {
        // Permetti sync da prestampa (hanno redirect)
        if (!request('redirect')) {
            if ($deny = $this->denyIfReadonly()) return $deny;
        }
        try {
            // Prima pulisci eventuali duplicati
            $duplicatiRimossi = $this->pulisciDuplicati();

            $risultato = OndaSyncService::sincronizza();
            $ddtFornitore = OndaSyncService::sincronizzaDDTFornitore();
            $ddtLavorazioni = OndaSyncService::sincronizzaDDTFornitureLavorazioni();
            $ddtVendita = OndaSyncService::sincronizzaDDTVendita();
            $msg = "Sync Onda completato: {$risultato['ordini_creati']} ordini creati, "
                 . "{$risultato['ordini_aggiornati']} aggiornati, {$risultato['fasi_create']} fasi create.";
            $totDDT = $ddtFornitore + $ddtLavorazioni;
            if ($totDDT > 0) $msg .= " DDT fornitore: {$totDDT} fasi esterne.";
            if ($ddtVendita > 0) $msg .= " DDT vendita: {$ddtVendita}.";
            if ($duplicatiRimossi > 0) {
                $msg .= " ($duplicatiRimossi ordini duplicati rimossi)";
            }
            $redirectUrl = request('redirect');
            if ($redirectUrl) {
                return redirect($redirectUrl)->with('success', $msg);
            }
            return redirect()->route('owner.dashboard')->with('success', $msg);
        } catch (\Exception $e) {
            $redirectUrl = request('redirect');
            if ($redirectUrl) {
                return redirect($redirectUrl)->with('error', 'Errore sync Onda: ' . $e->getMessage());
            }
            return redirect()->route('owner.dashboard')->with('error', 'Errore sync Onda: ' . $e->getMessage());
        }
    }

    /**
     * Rimuovi ordini duplicati: per ogni (commessa, cod_art), tieni quello con id più basso
     * e sposta le fasi con stato > 0 dal duplicato all'originale prima di eliminare.
     */
    private function pulisciDuplicati(): int
    {
        $rimossi = 0;

        // Trova gruppi con duplicati
        $duplicati = Ordine::select('commessa', 'cod_art', \DB::raw('COUNT(*) as cnt'), \DB::raw('MIN(id) as min_id'))
            ->groupBy('commessa', 'cod_art')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicati as $gruppo) {
            $originaleId = $gruppo->min_id;

            // Ordini duplicati (tutti tranne l'originale)
            $ordiniDuplicati = Ordine::where('commessa', $gruppo->commessa)
                ->where('cod_art', $gruppo->cod_art)
                ->where('id', '!=', $originaleId)
                ->get();

            foreach ($ordiniDuplicati as $dup) {
                // Sposta fasi con stato > 0 (lavorate) all'ordine originale
                OrdineFase::where('ordine_id', $dup->id)
                    ->where('stato', '>', 0)
                    ->update(['ordine_id' => $originaleId]);

                // Elimina le fasi rimanenti del duplicato
                OrdineFase::where('ordine_id', $dup->id)->delete();

                // Elimina l'ordine duplicato
                $dup->delete();
                $rimossi++;
            }
        }

        return $rimossi;
    }

    public function reportOre(Request $request)
    {
        $filtroCommessa = $request->query('commessa');
        $filtroReparto = $request->query('reparto');
        $filtroDal = $request->query('dal');
        $filtroAl = $request->query('al');

        // Escludi reparto spedizione
        $repartoSpedizione = Reparto::where('nome', 'spedizione')->first();

        $query = OrdineFase::with(['ordine.fasi.faseCatalogo', 'faseCatalogo.reparto', 'operatori'])
            ->whereHas('ordine');

        // Escludi BRT e reparto spedizione
        $query->whereNotIn('fase', ['BRT1', 'brt1', 'BRT']);
        if ($repartoSpedizione) {
            $query->where(function ($q) use ($repartoSpedizione) {
                $q->whereDoesntHave('faseCatalogo')
                  ->orWhereHas('faseCatalogo', fn($q2) => $q2->where('reparto_id', '!=', $repartoSpedizione->id));
            });
        }

        if ($filtroCommessa) {
            $query->whereHas('ordine', fn($q) => $q->where('commessa', 'like', "%{$filtroCommessa}%"));
        }
        if ($filtroReparto) {
            $query->whereHas('faseCatalogo', fn($q) => $q->where('reparto_id', $filtroReparto));
        }

        // Filtro date
        if ($filtroDal) {
            $query->where('data_fine', '>=', Carbon::parse($filtroDal)->startOfDay());
        }
        if ($filtroAl) {
            $query->where('data_fine', '<=', Carbon::parse($filtroAl)->endOfDay());
        }

        // Solo fasi terminate o consegnate (stato 3/4)
        $fasi = $query->where('stato', '>', 2)
            ->get()
            ->map(function ($fase) {
                // Ore previste
                $qta_carta = $fase->ordine->qta_carta ?? 0;
                $infoFase = $this->fasiInfo[$fase->fase] ?? ['avviamento' => 0.5, 'copieh' => 1000];
                $copieh = $infoFase['copieh'] ?: 1000;
                $fase->ore_previste = round($infoFase['avviamento'] + ($qta_carta / $copieh), 2);

                // Ore lavorate: prima da Prinect (tempo_avviamento + tempo_esecuzione), poi da pivot operatore
                $secPrinect = ($fase->tempo_avviamento_sec ?? 0) + ($fase->tempo_esecuzione_sec ?? 0);
                if ($secPrinect > 0) {
                    $fase->ore_lavorate = round($secPrinect / 3600, 2);
                    $fase->fonte_ore = 'Prinect';
                } else {
                    $oreTotali = 0;
                    foreach ($fase->operatori as $op) {
                        $inizio = $op->pivot->data_inizio;
                        $fine = $op->pivot->data_fine;
                        $pausa = $op->pivot->secondi_pausa ?? 0;
                        if ($inizio && $fine) {
                            $secondi = Carbon::parse($inizio)->diffInSeconds(Carbon::parse($fine));
                            $secondi = max(0, $secondi - $pausa);
                            $oreTotali += $secondi / 3600;
                        }
                    }
                    $fase->ore_lavorate = round($oreTotali, 2);
                    $fase->fonte_ore = $oreTotali > 0 ? 'MES' : '';
                }

                return $fase;
            });

        // Raggruppa per commessa
        $commesse = $fasi->groupBy(fn($f) => $f->ordine->commessa ?? '-')->map(function ($fasiCommessa, $commessa) {
            $first = $fasiCommessa->first();
            $ordine = $first->ordine;
            return (object) [
                'commessa' => $commessa,
                'cliente' => $ordine->cliente_nome ?? '-',
                'descrizione' => $ordine->descrizione ?? '-',
                'data_consegna' => $ordine->data_prevista_consegna,
                'responsabile' => $ordine->responsabile ?? '-',
                'ore_previste' => round($fasiCommessa->sum('ore_previste'), 2),
                'ore_lavorate' => round($fasiCommessa->sum('ore_lavorate'), 2),
                'fasi' => $fasiCommessa->sortBy(fn($f) => config('fasi_priorita')[$f->fase] ?? 500),
                'num_fasi' => $fasiCommessa->count(),
                'num_terminate' => $fasiCommessa->where('stato', '>=', 3)->count(),
                'num_avviate' => $fasiCommessa->where('stato', 2)->count(),
            ];
        })->sortBy('commessa');

        // Statistiche per reparto
        $orePerReparto = $fasi->groupBy(fn($f) => optional($f->faseCatalogo)->reparto->nome ?? 'Altro')
            ->map(fn($group) => (object)[
                'previste' => round($group->sum('ore_previste'), 1),
                'lavorate' => round($group->sum('ore_lavorate'), 1),
                'fasi' => $group->count(),
            ])->sortByDesc('lavorate');

        $reparti = Reparto::where('nome', '!=', 'spedizione')->orderBy('nome')->pluck('nome', 'id');

        // Commesse con fasi terminate oggi
        $oggi = Carbon::today();
        $commesseOggi = $commesse->filter(function ($c) use ($oggi) {
            return $c->fasi->contains(function ($f) use ($oggi) {
                if (!$f->data_fine) return false;
                try {
                    return Carbon::parse($f->data_fine)->isToday();
                } catch (\Exception $e) {
                    return false;
                }
            });
        });

        return view('owner.report_ore', compact('commesse', 'commesseOggi', 'reparti', 'filtroCommessa', 'filtroReparto', 'filtroDal', 'filtroAl', 'orePerReparto'));
    }

    /**
     * Prototipo nuova UI owner — dati reali, layout nuovo.
     */
    public function prototipo()
    {
        // KPI
        $oggi = Carbon::today();
        $fasiCompletateOggi = OrdineFase::where('stato', '>=', 3)
            ->where(function ($q) use ($oggi) {
                $q->whereHas('operatori', fn($q2) => $q2->whereDate('fase_operatore.data_fine', $oggi))
                  ->orWhereDate('data_fine', $oggi);
            })->count();
        $fasiAttive = OrdineFase::where('stato', 2)->count();
        $commesseSpediteOggi = OrdineFase::where('stato', 4)
            ->whereDate('data_fine', $oggi)->with('ordine')->get()
            ->pluck('ordine.commessa')->unique()->count();

        $pivotOggi = DB::table('fase_operatore')->whereDate('data_fine', $oggi)
            ->whereNotNull('data_inizio')->select('data_inizio', 'data_fine', 'secondi_pausa')->get();
        $oreLavorateOggi = round($pivotOggi->sum(function ($row) {
            return max(abs(Carbon::parse($row->data_fine)->diffInSeconds(Carbon::parse($row->data_inizio))) - ($row->secondi_pausa ?? 0), 0) / 3600;
        }), 1);

        return view('proto.owner_full', compact(
            'fasiCompletateOggi', 'fasiAttive', 'commesseSpediteOggi', 'oreLavorateOggi'
        ));
    }

    public function scheduling(PrinectService $prinect, PrinectSyncService $syncService)
    {
        // Sync live Prinect
        try {
            $deviceId = env('PRINECT_DEVICE_XL106_ID', '4001');
            $oggi = Carbon::today()->format('Y-m-d\TH:i:sP');
            $ora = Carbon::now()->format('Y-m-d\TH:i:sP');
            $apiOggi = $prinect->getDeviceActivity($deviceId, $oggi, $ora);
            $rawOggi = collect($apiOggi['activities'] ?? [])
                ->filter(fn($a) => !empty($a['id']))
                ->values()
                ->toArray();
            $syncService->sincronizzaDaLive($rawOggi);
        } catch (\Exception $e) {}

        // Calcola tempi medi storici per tipo fase (da Prinect e operatori)
        $tempiMediPerFase = [];
        $fasiCompletate = OrdineFase::with(['ordine', 'operatori'])
            ->where('stato', '>=', 3)
            ->where('data_fine', '>=', Carbon::now()->subDays(90))
            ->get();
        foreach ($fasiCompletate as $fc) {
            $tipo = $fc->fase;
            $qtaCarta = $fc->ordine->qta_carta ?? 0;
            if ($qtaCarta <= 0) continue;

            // Ore effettive: prima Prinect, poi pivot operatore
            $ore = null;
            $secP = ($fc->tempo_avviamento_sec ?? 0) + ($fc->tempo_esecuzione_sec ?? 0);
            if ($secP > 60) {
                $ore = $secP / 3600;
            } else {
                // Calcola da operatori pivot
                if ($fc->operatori->isNotEmpty()) {
                    $secOp = 0;
                    foreach ($fc->operatori as $op) {
                        if ($op->pivot->data_inizio && $op->pivot->data_fine) {
                            $s = Carbon::parse($op->pivot->data_fine)->diffInSeconds(Carbon::parse($op->pivot->data_inizio));
                            $s -= ($op->pivot->secondi_pausa ?? 0);
                            if ($s > 0) $secOp += $s;
                        }
                    }
                    if ($secOp > 60) $ore = $secOp / 3600;
                }
            }
            if ($ore && $ore > 0.01 && $ore < 200) {
                if (!isset($tempiMediPerFase[$tipo])) $tempiMediPerFase[$tipo] = ['totOre' => 0, 'totQta' => 0, 'count' => 0];
                $tempiMediPerFase[$tipo]['totOre'] += $ore;
                $tempiMediPerFase[$tipo]['totQta'] += $qtaCarta;
                $tempiMediPerFase[$tipo]['count']++;
            }
        }
        // Calcola ore medie per foglio e avviamento medio
        $tempiMedi = [];
        foreach ($tempiMediPerFase as $tipo => $dati) {
            if ($dati['count'] >= 2 && $dati['totQta'] > 0) {
                $tempiMedi[$tipo] = round($dati['totOre'] / $dati['count'], 3); // ore medie per job
            }
        }

        $fasi = OrdineFase::with(['ordine', 'faseCatalogo.reparto', 'operatori'])
            ->where('stato', '<', 3)
            ->get()
            ->map(function ($fase) {
                return $this->calcolaOreEPriorita($fase);
            })
            ->sortBy('priorita');

        $dataScheduling = $fasi->map(function ($fase) use ($tempiMedi) {
            // Data inizio: dalla pivot operatore, fallback dal campo ordine_fasi
            $dataInizio = null;
            if ($fase->operatori->isNotEmpty()) {
                $primo = $fase->operatori->sortBy('pivot.data_inizio')->first();
                $dataInizio = $primo?->pivot->data_inizio;
            }
            if (!$dataInizio) {
                $dataInizio = $fase->getAttributes()['data_inizio'] ?? null;
            }

            $consegna = $fase->ordine->data_prevista_consegna;
            $giorniAllaConsegna = null;
            if ($consegna) {
                $giorniAllaConsegna = round((Carbon::parse($consegna)->startOfDay()->timestamp - Carbon::today()->timestamp) / 86400);
            }

            // Ore reali da Prinect (se disponibili)
            $orePrinect = null;
            $secPrinect = ($fase->tempo_avviamento_sec ?? 0) + ($fase->tempo_esecuzione_sec ?? 0);
            if ($secPrinect > 0) {
                $orePrinect = round($secPrinect / 3600, 2);
            }

            // Ore reali da pivot operatore (data_fine - data_inizio - pausa)
            $oreOperatore = null;
            if ($fase->operatori->isNotEmpty()) {
                $oreOp = 0;
                foreach ($fase->operatori as $op) {
                    if ($op->pivot->data_inizio && $op->pivot->data_fine) {
                        $sec = Carbon::parse($op->pivot->data_fine)->diffInSeconds(Carbon::parse($op->pivot->data_inizio));
                        $sec -= ($op->pivot->secondi_pausa ?? 0);
                        if ($sec > 0) $oreOp += $sec / 3600;
                    }
                }
                if ($oreOp > 0) $oreOperatore = round($oreOp, 2);
            }

            // Ordine nel flusso produttivo (da config fasi_priorita)
            $faseOrdine = config('fasi_priorita')[$fase->fase] ?? 500;

            return [
                'id' => $fase->id,
                'commessa' => $fase->ordine->commessa ?? '-',
                'cliente' => $fase->ordine->cliente_nome ?? '-',
                'descrizione' => $fase->ordine->descrizione ?? '-',
                'cod_art' => $fase->ordine->cod_art ?? '-',
                'fase' => $fase->fase,
                'stato' => $fase->stato,
                'ore' => round($fase->ore, 2),
                'ore_prinect' => $orePrinect,
                'ore_operatore' => $oreOperatore,
                'ore_media_storica' => $tempiMedi[$fase->fase] ?? null,
                'fase_ordine' => $faseOrdine,
                'priorita' => $fase->priorita,
                'consegna' => $consegna,
                'giorni_consegna' => $giorniAllaConsegna,
                'qta_carta' => $fase->ordine->qta_carta ?? 0,
                'reparto_id' => $fase->faseCatalogo?->reparto_id ?? 0,
                'reparto' => ucfirst($fase->faseCatalogo?->reparto?->nome ?? 'generico'),
                'data_inizio_reale' => $dataInizio,
            ];
        })->values();

        return view('owner.scheduling', [
            'dataJson' => $dataScheduling->toJson(),
        ]);
    }

    public function downloadExcel()
    {
        // Genera file aggiornato
        ExcelSyncService::exportToExcel();

        $path = env('EXCEL_SYNC_PATH');
        if ($path && $path !== '') {
            $filePath = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . 'dashboard_mes.xlsx';
        } else {
            $filePath = storage_path('app/excel_sync/dashboard_mes.xlsx');
        }

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'File Excel non ancora generato.');
        }

        return response()->download($filePath, 'dashboard_mes.xlsx');
    }

    public function esterne()
    {
        $repartoEsterno = Reparto::where('nome', 'esterno')->first();

        // Fasi esterne: reparto "esterno" OPPURE flag esterno=1 (inviate dal owner)
        // Include stato < 3 (attive) e stato 5 (esterno dedicato)
        $fasiEsterne = OrdineFase::where(fn($q) => $q->where('stato', '<', 3)->orWhere('stato', 5))
            ->where(function ($q) use ($repartoEsterno) {
                if ($repartoEsterno) {
                    $q->whereHas('faseCatalogo', fn($q2) => $q2->where('reparto_id', $repartoEsterno->id));
                }
                $q->orWhere('esterno', 1);
            })
            ->with(['ordine.fasi.faseCatalogo', 'faseCatalogo'])
            ->get();

        $commesseEsterne = collect();
        if ($fasiEsterne->isNotEmpty()) {

            // Raggruppa per commessa, solo quelle con fornitore (DDT sincronizzata)
            $commesseEsterne = $fasiEsterne->groupBy('ordine_id')->map(function ($fasi) {
                $prima = $fasi->first();
                $ordine = $prima->ordine;

                // Estrai fornitore dalla nota della prima fase con "Inviato a:"
                $fornitore = '-';
                foreach ($fasi as $f) {
                    if (preg_match('/Inviato a:\s*(.+)/i', $f->note ?? '', $mf)) {
                        $fornitore = trim($mf[1]);
                        break;
                    }
                }

                // Stato complessivo: il "peggiore" (priorità: pausa > in corso > pronto > da fare)
                $stati = $fasi->pluck('stato');
                if ($stati->contains(fn($s) => is_string($s) && !is_numeric($s))) {
                    $statoPausa = $fasi->first(fn($f) => is_string($f->stato) && !is_numeric($f->stato));
                    $stato = $statoPausa->stato;
                } elseif ($stati->contains(5)) {
                    $stato = 5;
                } elseif ($stati->contains(2)) {
                    $stato = 2;
                } elseif ($stati->contains(1)) {
                    $stato = 1;
                } else {
                    $stato = 0;
                }

                // Data invio: la prima data_inizio disponibile
                $dataInvio = $fasi->whereNotNull('data_inizio')->min('data_inizio');

                return (object) [
                    'ordine'        => $ordine,
                    'fornitore'     => $fornitore,
                    'stato'         => $stato,
                    'fasi'          => $fasi->pluck('faseCatalogo.nome_display', 'id')->filter()->values()->implode(', ') ?: $fasi->pluck('fase')->implode(', '),
                    'fasi_dettaglio' => $fasi->map(fn($f) => [
                        'id' => $f->id,
                        'nome' => $f->faseCatalogo->nome_display ?? $f->fase,
                        'qta' => $f->qta_fase ?? $f->ordine->qta_richiesta ?? 0,
                    ])->values()->toArray(),
                    'num_fasi'      => $fasi->count(),
                    'fasi_ids'      => $fasi->pluck('id')->values()->toArray(),
                    'data_invio'    => $dataInvio,
                    'note'          => $fasi->pluck('note')->filter()->unique()->implode(' | '),
                ];
            })->sortBy(fn($c) => $c->ordine->data_prevista_consegna ?? '9999-12-31')->values();
        }

        return view('owner.esterne', compact('commesseEsterne'));
    }

    /**
     * Pagina Fustelle owner: tutte le fustelle da utilizzare nei prossimi 30 giorni.
     */
    public function fustelleOverview()
    {
        $repartiFustella = [5, 15, 16]; // fustella, fustella piana, fustella cilindrica

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

        $fustelleMap = [];
        foreach ($fasi as $fase) {
            $desc = $fase->ordine->descrizione ?? '';
            $cliente = $fase->ordine->cliente_nome ?? '';
            $notePre = $fase->ordine->note_prestampa ?? '';
            $fsCodice = DescrizioneParser::parseFustella($desc, $cliente, $notePre);

            if (!$fsCodice) continue;

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

        ksort($fustelleMap);

        return view('owner.fustelle', compact('fustelleMap'));
    }

    /**
     * Audit Log — visualizzazione eventi di sicurezza
     */
    public function auditLog(Request $request)
    {
        $query = DB::table('audit_logs')->orderByDesc('created_at');

        if ($request->filled('azione')) {
            $query->where('action', $request->azione);
        }
        if ($request->filled('utente')) {
            $query->where('user_name', 'like', '%' . $request->utente . '%');
        }
        if ($request->filled('data')) {
            $query->whereDate('created_at', $request->data);
        }

        $logs = $query->paginate(50);

        $azioni = DB::table('audit_logs')->distinct()->pluck('action');

        return view('owner.audit_log', compact('logs', 'azioni'));
    }
}