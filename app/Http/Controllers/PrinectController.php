<?php

namespace App\Http\Controllers;

use App\Http\Services\PrinectService;
use App\Models\PrinectAttivita;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PrinectController extends Controller
{
    public function index(PrinectService $service)
    {
        $deviceId = env('PRINECT_DEVICE_XL106_ID', '4001');

        // Dati live dalla macchina
        $devices = $service->getDevices();
        $device = $devices['devices'][0] ?? null;

        // Attivita ultime 24h dal DB locale
        $attivitaOggi = PrinectAttivita::where('start_time', '>=', Carbon::today())
            ->orderByDesc('start_time')
            ->get();

        // Attivita ultimi 7 giorni per grafici
        $attivita7gg = PrinectAttivita::where('start_time', '>=', Carbon::now()->subDays(7))
            ->orderBy('start_time')
            ->get();

        // KPI ultimi 7 giorni
        $totBuoni7gg = $attivita7gg->sum('good_cycles');
        $totScarto7gg = $attivita7gg->sum('waste_cycles');
        $totFogli7gg = $totBuoni7gg + $totScarto7gg;
        $percScarto7gg = $totFogli7gg > 0 ? round(($totScarto7gg / $totFogli7gg) * 100, 1) : 0;

        // KPI oggi
        $totBuoniOggi = $attivitaOggi->sum('good_cycles');
        $totScartoOggi = $attivitaOggi->sum('waste_cycles');
        $totFogliOggi = $totBuoniOggi + $totScartoOggi;
        $percScartoOggi = $totFogliOggi > 0 ? round(($totScartoOggi / $totFogliOggi) * 100, 1) : 0;

        // Tempo avviamento vs produzione oggi
        $secAvvOggi = 0;
        $secProdOggi = 0;
        foreach ($attivitaOggi as $att) {
            if (!$att->start_time || !$att->end_time) continue;
            $diff = $att->start_time->diffInSeconds($att->end_time);
            if ($att->activity_name === 'Avviamento') {
                $secAvvOggi += $diff;
            } else {
                $secProdOggi += $diff;
            }
        }

        // Produzione per giorno (ultimi 7gg) per grafico a barre
        $prodPerGiorno = $attivita7gg->groupBy(fn($a) => $a->start_time->format('Y-m-d'))
            ->map(function ($gruppo) {
                return [
                    'buoni' => $gruppo->sum('good_cycles'),
                    'scarto' => $gruppo->sum('waste_cycles'),
                    'sec_avv' => $gruppo->where('activity_name', 'Avviamento')
                        ->sum(fn($a) => $a->start_time && $a->end_time ? $a->start_time->diffInSeconds($a->end_time) : 0),
                    'sec_prod' => $gruppo->where('activity_name', '!=', 'Avviamento')
                        ->sum(fn($a) => $a->start_time && $a->end_time ? $a->start_time->diffInSeconds($a->end_time) : 0),
                ];
            })->sortKeys();

        // Per operatore (ultimi 7gg)
        $perOperatore = collect();
        foreach ($attivita7gg as $att) {
            $nome = $att->operatore_prinect ?: 'N/D';
            if (!$perOperatore->has($nome)) {
                $perOperatore[$nome] = (object) [
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

        // Top commesse ultimi 7gg
        $topCommesse = $attivita7gg->whereNotNull('commessa_gestionale')
            ->groupBy('commessa_gestionale')
            ->map(function ($gruppo) {
                return (object) [
                    'commessa' => $gruppo->first()->commessa_gestionale,
                    'job_name' => $gruppo->first()->prinect_job_name,
                    'buoni' => $gruppo->sum('good_cycles'),
                    'scarto' => $gruppo->sum('waste_cycles'),
                    'n_attivita' => $gruppo->count(),
                ];
            })->sortByDesc('buoni')->take(10);

        // Consumption (cambi lastra)
        $start7gg = Carbon::now()->subDays(7)->format('Y-m-d\TH:i:sP');
        $endNow = Carbon::now()->format('Y-m-d\TH:i:sP');
        $consumption = $service->getDeviceConsumption($deviceId, $start7gg, $endNow);
        $cambiLastra = $consumption['plateChanges'] ?? 0;

        // OEE (Overall Equipment Effectiveness) - ultimi 7 giorni
        // Turno stampa offset: 23h/giorno (0-23), velocita nominale XL106: 18000 fogli/h
        $velocitaNominale = 18000;
        $oreTurnoPianificate7gg = 23 * 7; // ore pianificate 7 giorni
        $secTotali7gg = $attivita7gg->sum(function ($a) {
            return ($a->start_time && $a->end_time) ? $a->start_time->diffInSeconds($a->end_time) : 0;
        });
        $secProd7gg = $attivita7gg->where('activity_name', '!=', 'Avviamento')
            ->sum(function ($a) {
                return ($a->start_time && $a->end_time) ? $a->start_time->diffInSeconds($a->end_time) : 0;
            });

        $disponibilita = $oreTurnoPianificate7gg > 0 ? min($secTotali7gg / ($oreTurnoPianificate7gg * 3600), 1) : 0;
        $performance = ($secProd7gg > 0 && $velocitaNominale > 0) ? min(($totBuoni7gg + $totScarto7gg) / (($secProd7gg / 3600) * $velocitaNominale), 1) : 0;
        $qualita = $totFogli7gg > 0 ? $totBuoni7gg / $totFogli7gg : 0;
        $oee = round($disponibilita * $performance * $qualita * 100, 1);
        $oeeDisp = round($disponibilita * 100, 1);
        $oeePerf = round($performance * 100, 1);
        $oeeQual = round($qualita * 100, 1);

        // Dati live dal workstep in corso (non ancora in DB)
        $liveProduced = $device['deviceStatus']['workstep']['amountProduced'] ?? 0;
        $liveWaste = $device['deviceStatus']['workstep']['wasteProduced'] ?? 0;

        // Alert automatici
        $alerts = [];

        // 1. Macchina ferma
        $statoMacchina = $device['deviceStatus']['status'] ?? 'Idle';
        if (in_array($statoMacchina, ['Stopped', 'Error'])) {
            $alerts[] = ['tipo' => 'danger', 'msg' => 'Macchina FERMA — stato: ' . $statoMacchina];
        }

        // 2. Scarti sopra soglia (>5% oggi)
        if ($percScartoOggi > 5 && $totFogliOggi > 100) {
            $alerts[] = ['tipo' => 'warning', 'msg' => 'Scarto oggi al ' . $percScartoOggi . '% — sopra soglia 5%'];
        }

        // 3. Cambi lastra eccessivi (>50 in 7gg)
        if ($cambiLastra > 50) {
            $alerts[] = ['tipo' => 'warning', 'msg' => $cambiLastra . ' cambi lastra in 7 giorni — verificare setup'];
        }

        // 4. OEE basso (<40%)
        if ($oee > 0 && $oee < 40) {
            $alerts[] = ['tipo' => 'danger', 'msg' => 'OEE critico: ' . $oee . '% — sotto soglia 40%'];
        }

        // Timeline attivita oggi per grafico
        $timelineOggi = $attivitaOggi->filter(fn($a) => $a->start_time && $a->end_time)
            ->map(function ($att) {
                return [
                    'start' => $att->start_time->toIso8601String(),
                    'tipo' => $att->activity_name,
                    'durata_min' => round($att->start_time->diffInSeconds($att->end_time) / 60, 1),
                    'workstep' => $att->workstep_name ?? '-',
                    'buoni' => $att->good_cycles,
                    'scarto' => $att->waste_cycles,
                    'operatore' => $att->operatore_prinect ?? '-',
                ];
            })->values();

        return view('mes.prinect_dashboard', compact(
            'device',
            'attivitaOggi',
            'totBuoniOggi', 'totScartoOggi', 'totFogliOggi', 'percScartoOggi',
            'totBuoni7gg', 'totScarto7gg', 'totFogli7gg', 'percScarto7gg',
            'secAvvOggi', 'secProdOggi',
            'prodPerGiorno', 'perOperatore', 'topCommesse',
            'cambiLastra', 'timelineOggi',
            'oee', 'oeeDisp', 'oeePerf', 'oeeQual',
            'alerts',
            'liveProduced', 'liveWaste'
        ));
    }

    /**
     * AJAX: stato macchina live (auto-refresh)
     */
    public function apiStatus(PrinectService $service)
    {
        $devices = $service->getDevices();
        $device = $devices['devices'][0] ?? null;

        if (!$device) {
            return response()->json(['error' => 'Nessun dispositivo'], 500);
        }

        $ws = $device['deviceStatus']['workstep'] ?? [];
        $employees = $device['deviceStatus']['employees'] ?? [];
        $operatori = array_map(fn($e) => ($e['firstName'] ?? '') . ' ' . ($e['name'] ?? ''), $employees);

        return response()->json([
            'status'     => $device['deviceStatus']['status'] ?? '-',
            'activity'   => $device['deviceStatus']['activity'] ?? '-',
            'speed'      => $device['deviceStatus']['speed'] ?? 0,
            'totalizer'  => $device['deviceStatus']['totalizer'] ?? 0,
            'job_name'   => $ws['job']['name'] ?? '-',
            'job_id'     => $ws['job']['id'] ?? '-',
            'workstep'   => $ws['name'] ?? '-',
            'ws_status'  => $ws['status'] ?? '-',
            'produced'   => $ws['amountProduced'] ?? 0,
            'waste'      => $ws['wasteProduced'] ?? 0,
            'planned'    => $ws['amountPlanned'] ?? 0,
            'operatori'  => implode(', ', $operatori),
            'tempi'      => $ws['actualTimes'] ?? [],
        ]);
    }

    /**
     * Storico attivita con filtri e paginazione
     */
    public function attivita(Request $request)
    {
        $query = PrinectAttivita::query();

        if ($request->filled('job')) {
            $query->where('prinect_job_id', $request->job);
        }
        if ($request->filled('tipo')) {
            $query->where('activity_name', $request->tipo);
        }
        if ($request->filled('da')) {
            $query->whereDate('start_time', '>=', $request->da);
        }
        if ($request->filled('a')) {
            $query->whereDate('start_time', '<=', $request->a);
        }

        $riepilogoQuery = clone $query;
        $riepilogoJobs = $riepilogoQuery
            ->selectRaw('prinect_job_id, prinect_job_name, commessa_gestionale,
                SUM(good_cycles) as total_good,
                SUM(waste_cycles) as total_waste,
                COUNT(*) as count')
            ->groupBy('prinect_job_id', 'prinect_job_name', 'commessa_gestionale')
            ->orderByDesc('count')
            ->get();

        $attivita = PrinectAttivita::query();
        if ($request->filled('job')) $attivita->where('prinect_job_id', $request->job);
        if ($request->filled('tipo')) $attivita->where('activity_name', $request->tipo);
        if ($request->filled('da')) $attivita->whereDate('start_time', '>=', $request->da);
        if ($request->filled('a')) $attivita->whereDate('start_time', '<=', $request->a);
        $attivita = $attivita->orderByDesc('start_time')->paginate(50);

        return view('mes.prinect_attivita', compact('riepilogoJobs', 'attivita'));
    }

    /**
     * Report commessa con KPI e grafici
     */
    public function reportCommessa($commessa)
    {
        $attivita = PrinectAttivita::where('commessa_gestionale', $commessa)
            ->orderBy('start_time')
            ->get();

        if ($attivita->isEmpty()) {
            return back()->with('error', 'Nessuna attivita Prinect trovata per la commessa ' . $commessa . '. Il job potrebbe non essere ancora stato lavorato sulla XL106.');
        }

        $jobName = $attivita->first()->prinect_job_name ?? '-';
        $jobId = $attivita->first()->prinect_job_id ?? '-';

        $totBuoni = $attivita->sum('good_cycles');
        $totScarto = $attivita->sum('waste_cycles');
        $totFogli = $totBuoni + $totScarto;
        $percScarto = $totFogli > 0 ? round(($totScarto / $totFogli) * 100, 1) : 0;

        $tempoAvviamentoSec = 0;
        $tempoProduzioneSec = 0;

        foreach ($attivita as $att) {
            if (!$att->start_time || !$att->end_time) continue;
            $diff = $att->start_time->diffInSeconds($att->end_time);
            if ($att->activity_name === 'Avviamento') {
                $tempoAvviamentoSec += $diff;
            } else {
                $tempoProduzioneSec += $diff;
            }
        }

        $tempoTotaleSec = $tempoAvviamentoSec + $tempoProduzioneSec;

        $perWorkstep = $attivita->groupBy('workstep_name')->map(function ($gruppo) {
            return (object) [
                'buoni' => $gruppo->sum('good_cycles'),
                'scarto' => $gruppo->sum('waste_cycles'),
                'sec_avviamento' => $gruppo->where('activity_name', 'Avviamento')
                    ->sum(fn($a) => $a->start_time && $a->end_time ? $a->start_time->diffInSeconds($a->end_time) : 0),
                'sec_produzione' => $gruppo->where('activity_name', '!=', 'Avviamento')
                    ->sum(fn($a) => $a->start_time && $a->end_time ? $a->start_time->diffInSeconds($a->end_time) : 0),
                'n_attivita' => $gruppo->count(),
            ];
        });

        $perOperatore = collect();
        foreach ($attivita as $att) {
            $nome = $att->operatore_prinect ?: '-';
            if (!$perOperatore->has($nome)) {
                $perOperatore[$nome] = (object) ['buoni' => 0, 'scarto' => 0, 'sec_totali' => 0, 'n_attivita' => 0];
            }
            $perOperatore[$nome]->buoni += $att->good_cycles;
            $perOperatore[$nome]->scarto += $att->waste_cycles;
            $perOperatore[$nome]->sec_totali += ($att->start_time && $att->end_time) ? $att->start_time->diffInSeconds($att->end_time) : 0;
            $perOperatore[$nome]->n_attivita++;
        }

        $chartData = $attivita->filter(fn($a) => $a->start_time && $a->end_time)->map(function ($att) {
            return [
                'start' => $att->start_time->toIso8601String(),
                'tipo' => $att->activity_name,
                'durata_min' => round($att->start_time->diffInSeconds($att->end_time) / 60, 1),
                'workstep' => $att->workstep_name ?? '-',
                'buoni' => $att->good_cycles,
                'scarto' => $att->waste_cycles,
                'operatore' => $att->operatore_prinect ?? '-',
            ];
        })->values();

        return view('mes.prinect_report_commessa', compact(
            'commessa', 'jobName', 'jobId',
            'totBuoni', 'totScarto', 'totFogli', 'percScarto',
            'tempoAvviamentoSec', 'tempoProduzioneSec', 'tempoTotaleSec',
            'perWorkstep', 'perOperatore', 'attivita', 'chartData'
        ));
    }

    /**
     * Lista job recenti con progresso
     */
    public function jobs(PrinectService $service)
    {
        $since = Carbon::now()->subDays(30)->format('Y-m-d\TH:i:sP');
        $data = $service->getJobs($since);
        $jobs = $data['jobs'] ?? [];

        // Filtra solo job con id numerico e ordina per id desc
        $jobs = collect($jobs)->filter(fn($j) => is_numeric($j['id']))->sortByDesc('id')->values();

        // Commesse che hanno attivita di stampa sincronizzate
        $commesseConAttivita = PrinectAttivita::whereNotNull('commessa_gestionale')
            ->distinct()
            ->pluck('commessa_gestionale')
            ->flip()
            ->toArray();

        return view('mes.prinect_jobs', compact('jobs', 'commesseConAttivita'));
    }

    /**
     * Dettaglio job con worksteps e ink consumption
     */
    public function jobDetail(PrinectService $service, $jobId)
    {
        $wsData = $service->getJobWorksteps($jobId);
        $worksteps = $wsData['worksteps'] ?? [];

        // Per ogni workstep di tipo stampa, prova a caricare ink
        foreach ($worksteps as &$ws) {
            $ws['ink'] = null;
            if (isset($ws['types']) && in_array('ConventionalPrinting', $ws['types'])) {
                $ink = $service->getWorkstepInkConsumption($jobId, $ws['id']);
                $ws['ink'] = $ink;
            }
        }

        return view('mes.prinect_job_detail', compact('jobId', 'worksteps'));
    }
}
