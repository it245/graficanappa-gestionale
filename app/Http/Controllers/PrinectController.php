<?php

namespace App\Http\Controllers;

use App\Http\Services\PrinectService;
use App\Http\Services\PrinectSyncService;
use App\Models\PrinectAttivita;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PrinectController extends Controller
{
    public function index(PrinectService $service, PrinectSyncService $syncService)
    {
        $deviceId = env('PRINECT_DEVICE_XL106_ID', '4001');

        // Dati live dalla macchina
        $devices = $service->getDevices();
        $device = $devices['devices'][0] ?? null;

        // ===== ATTIVITA OGGI: LIVE DALLA API PRINECT (non dal DB) =====
        $oggi = Carbon::today()->format('Y-m-d\TH:i:sP');
        $ora = Carbon::now()->format('Y-m-d\TH:i:sP');
        $apiOggi = $service->getDeviceActivity($deviceId, $oggi, $ora);
        $rawOggiArray = collect($apiOggi['activities'] ?? [])
            ->filter(fn($a) => !empty($a['id']))
            ->values()
            ->toArray();

        // Sync live: salva in DB + aggiorna fasi stampa (operatore, data_inizio, stato)
        try {
            $syncService->sincronizzaDaLive($rawOggiArray);
        } catch (\Exception $e) {
            // Non bloccare la dashboard se il sync fallisce
        }

        $attivitaOggi = collect($rawOggiArray)->map(function ($a) {
            $jobId = $a['workstep']['job']['id'] ?? null;
            return (object) [
                'activity_name' => $a['name'] ?? null,
                'good_cycles' => $a['goodCycles'] ?? 0,
                'waste_cycles' => $a['wasteCycles'] ?? 0,
                'start_time' => isset($a['startTime']) ? Carbon::parse($a['startTime']) : null,
                'end_time' => isset($a['endTime']) ? Carbon::parse($a['endTime']) : null,
                'prinect_job_name' => $a['workstep']['job']['name'] ?? null,
                'prinect_job_id' => $jobId,
                'workstep_name' => $a['workstep']['name'] ?? null,
                'operatore_prinect' => !empty($a['employees'])
                    ? implode(', ', array_map(fn($e) => trim(($e['firstName'] ?? '').' '.($e['name'] ?? '')), $a['employees']))
                    : null,
            ];
        })->sortByDesc('start_time')->values();

        // ===== ATTIVITA 7GG: LIVE DALLA API PRINECT =====
        $start7gg = Carbon::now()->subDays(7)->format('Y-m-d\TH:i:sP');
        $api7gg = $service->getDeviceActivity($deviceId, $start7gg, $ora);
        $raw7gg = collect($api7gg['activities'] ?? [])
            ->filter(fn($a) => !empty($a['id']));

        $attivita7gg = $raw7gg->map(function ($a) {
            $jobId = $a['workstep']['job']['id'] ?? null;
            $commessa = ($jobId && is_numeric($jobId))
                ? str_pad($jobId, 7, '0', STR_PAD_LEFT) . '-' . date('y', strtotime($a['startTime'] ?? 'now'))
                : null;
            return (object) [
                'activity_name' => $a['name'] ?? null,
                'good_cycles' => $a['goodCycles'] ?? 0,
                'waste_cycles' => $a['wasteCycles'] ?? 0,
                'start_time' => isset($a['startTime']) ? Carbon::parse($a['startTime']) : null,
                'end_time' => isset($a['endTime']) ? Carbon::parse($a['endTime']) : null,
                'prinect_job_name' => $a['workstep']['job']['name'] ?? null,
                'prinect_job_id' => $jobId,
                'commessa_gestionale' => $commessa,
                'workstep_name' => $a['workstep']['name'] ?? null,
                'operatore_prinect' => !empty($a['employees'])
                    ? implode(', ', array_map(fn($e) => trim(($e['firstName'] ?? '').' '.($e['name'] ?? '')), $a['employees']))
                    : null,
            ];
        })->sortBy('start_time')->values();

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

        // Consumption (lastre)
        $consumption = $service->getDeviceConsumption($deviceId, $start7gg, $ora);
        $cambiLastra = $consumption['plateChanges'] ?? 0;

        // Media lastre per commessa
        $nCommesse7gg = $attivita7gg->pluck('commessa_gestionale')->filter()->unique()->count();
        $mediaLastreCommessa = $nCommesse7gg > 0 ? round($cambiLastra / $nCommesse7gg, 1) : 0;

        // OEE (Overall Equipment Effectiveness) - ultimi 7 giorni
        $velocitaNominale = 18000;
        $oreTurnoPianificate7gg = 23 * 7;
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

        // Dati live dal workstep in corso (non ancora completato)
        $liveProduced = $device['deviceStatus']['workstep']['amountProduced'] ?? 0;
        $liveWaste = $device['deviceStatus']['workstep']['wasteProduced'] ?? 0;

        // Alert automatici
        $alerts = [];

        $statoMacchina = $device['deviceStatus']['status'] ?? 'Idle';
        if (in_array($statoMacchina, ['Stopped', 'Error'])) {
            $alerts[] = ['tipo' => 'danger', 'msg' => 'Macchina FERMA — stato: ' . $statoMacchina];
        }

        if ($percScartoOggi > 5 && $totFogliOggi > 100) {
            $alerts[] = ['tipo' => 'warning', 'msg' => 'Scarto oggi al ' . $percScartoOggi . '% — sopra soglia 5%'];
        }

        if ($cambiLastra > 50) {
            $alerts[] = ['tipo' => 'warning', 'msg' => $cambiLastra . ' cambi lastra in 7 giorni — verificare setup'];
        }

        if ($oee > 0 && $oee < 40) {
            $alerts[] = ['tipo' => 'danger', 'msg' => 'OEE critico: ' . $oee . '% — sotto soglia 40%'];
        }

        // Attivita oggi raggruppate per commessa (solo ultima per commessa)
        $attivitaOggiPerCommessa = $attivitaOggi->groupBy(function ($a) {
            $jId = $a->prinect_job_id ?? null;
            return ($jId && is_numeric($jId)) ? str_pad($jId, 7, '0', STR_PAD_LEFT) . '-' . date('y') : 'no-comm-' . spl_object_id($a);
        })->map(function ($gruppo) {
            $prima = $gruppo->first(); // gia ordinata per start_time desc, quindi la prima e la piu recente
            return (object) [
                'start_time' => $prima->start_time,
                'end_time' => $prima->end_time,
                'activity_name' => $prima->activity_name,
                'prinect_job_id' => $prima->prinect_job_id,
                'prinect_job_name' => $prima->prinect_job_name,
                'workstep_name' => $prima->workstep_name,
                'operatore_prinect' => $prima->operatore_prinect,
                'good_cycles' => $gruppo->sum('good_cycles'),
                'waste_cycles' => $gruppo->sum('waste_cycles'),
                'n_attivita' => $gruppo->count(),
                'sec_totali' => $gruppo->sum(function ($a) {
                    return ($a->start_time && $a->end_time) ? $a->start_time->diffInSeconds($a->end_time) : 0;
                }),
            ];
        })->sortByDesc('start_time')->values();

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
            'cambiLastra', 'mediaLastreCommessa', 'timelineOggi',
            'oee', 'oeeDisp', 'oeePerf', 'oeeQual',
            'alerts',
            'liveProduced', 'liveWaste',
            'attivitaOggiPerCommessa'
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
    public function reportCommessa($commessa, PrinectService $service)
    {
        $attivita = PrinectAttivita::where('commessa_gestionale', $commessa)
            ->orderBy('start_time')
            ->get();

        if ($attivita->isEmpty()) {
            return back()->with('error', 'Nessuna attivita Prinect trovata per la commessa ' . $commessa . '. Il job potrebbe non essere ancora stato lavorato sulla XL106.');
        }

        $jobName = $attivita->first()->prinect_job_name ?? '-';
        $jobId = $attivita->first()->prinect_job_id ?? '-';

        // Dati workstep da Prinect API (include volta e dati completi)
        $perWorkstep = collect();
        $totBuoni = 0;
        $totScarto = 0;
        $tempoAvviamentoSec = 0;
        $tempoProduzioneSec = 0;
        $usaWorkstep = false;

        if (is_numeric($jobId)) {
            try {
                $wsData = $service->getJobWorksteps($jobId);
                $worksteps = collect($wsData['worksteps'] ?? [])
                    ->filter(fn($ws) => in_array('ConventionalPrinting', $ws['types'] ?? []))
                    ->filter(fn($ws) => in_array($ws['status'] ?? '', ['COMPLETED', 'RUNNING']));

                if ($worksteps->isNotEmpty()) {
                    $usaWorkstep = true;

                    foreach ($worksteps as $ws) {
                        $nome = $ws['name'] ?? '-';
                        $buoni = $ws['amountProduced'] ?? 0;
                        $scarto = $ws['wasteProduced'] ?? 0;
                        $totBuoni += $buoni;
                        $totScarto += $scarto;

                        $times = collect($ws['actualTimes'] ?? []);
                        $secAvv = (int) ($times->firstWhere('timeTypeName', 'Tempo di avviamento')['duration'] ?? 0);
                        $secProd = (int) ($times->firstWhere('timeTypeName', 'Tempo di esecuzione')['duration'] ?? 0);
                        $tempoAvviamentoSec += $secAvv;
                        $tempoProduzioneSec += $secProd;

                        $perWorkstep[$nome] = (object) [
                            'buoni' => $buoni,
                            'scarto' => $scarto,
                            'sec_avviamento' => $secAvv,
                            'sec_produzione' => $secProd,
                            'n_attivita' => $attivita->where('workstep_name', $nome)->count() ?: '-',
                            'stato' => $ws['status'] ?? '-',
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Prinect non disponibile, fallback su attivita
            }
        }

        // Fallback: usa attivita se workstep non disponibili
        if (!$usaWorkstep) {
            $totBuoni = $attivita->sum('good_cycles');
            $totScarto = $attivita->sum('waste_cycles');

            foreach ($attivita as $att) {
                if (!$att->start_time || !$att->end_time) continue;
                $diff = $att->start_time->diffInSeconds($att->end_time);
                if ($att->activity_name === 'Avviamento') {
                    $tempoAvviamentoSec += $diff;
                } else {
                    $tempoProduzioneSec += $diff;
                }
            }

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
        }

        $totFogli = $totBuoni + $totScarto;
        $percScarto = $totFogli > 0 ? round(($totScarto / $totFogli) * 100, 1) : 0;
        $tempoTotaleSec = $tempoAvviamentoSec + $tempoProduzioneSec;

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
     * Lista job recenti con progresso e milestones decodificate
     */
    public function jobs(PrinectService $service)
    {
        // Cache risposta API Prinect per 5 minuti
        $cached = cache()->remember('prinect_jobs_list', 300, function () use ($service) {
            $data = $service->getJobs();
            $milestoneData = $service->getMilestones();
            return ['jobs' => $data['jobs'] ?? [], 'milestones' => $milestoneData['milestoneDefs'] ?? []];
        });

        $jobs = collect($cached['jobs'])->filter(fn($j) => is_numeric($j['id']))->sortByDesc('id')->values();

        $milestoneMap = [];
        foreach ($cached['milestones'] as $m) {
            $milestoneMap[$m['id']] = $m['name'];
        }

        $commesseConAttivita = PrinectAttivita::whereNotNull('commessa_gestionale')
            ->distinct()
            ->pluck('commessa_gestionale')
            ->flip()
            ->toArray();

        return view('mes.prinect_jobs', compact('jobs', 'commesseConAttivita', 'milestoneMap'));
    }

    /**
     * Dettaglio job completo: info live, worksteps, preview, elementi, ink
     */
    public function jobDetail(PrinectService $service, $jobId)
    {
        // Dettaglio job con creationDate, author, description
        $jobData = $service->getJob($jobId);
        $job = $jobData['job'] ?? null;

        // Worksteps
        $wsData = $service->getJobWorksteps($jobId);
        $worksteps = $wsData['worksteps'] ?? [];

        // Elementi: fogli, pagine, lastre
        $elemData = $service->getJobElements($jobId);
        $elements = $elemData['elements'] ?? [];

        // Milestones decodificate
        $milestoneData = $service->getMilestones();
        $milestoneMap = [];
        foreach (($milestoneData['milestoneDefs'] ?? []) as $m) {
            $milestoneMap[$m['id']] = $m['name'];
        }

        // Per ogni workstep di tipo stampa: ink + preview + quality
        $preview = null;
        foreach ($worksteps as &$ws) {
            $ws['ink'] = null;
            $ws['quality'] = null;
            if (isset($ws['types']) && in_array('ConventionalPrinting', $ws['types'])) {
                $ink = $service->getWorkstepInkConsumption($jobId, $ws['id']);
                $ws['ink'] = $ink;

                // Preview solo per il primo workstep di stampa
                if (!$preview) {
                    $prevData = $service->getWorkstepPreview($jobId, $ws['id']);
                    $previews = $prevData['previews'] ?? [];
                    if (!empty($previews)) {
                        $preview = $previews[0];
                    }
                }

                // Quality measurements
                $qualData = $service->getWorkstepQuality($jobId, $ws['id']);
                $measurements = $qualData['quality']['printQuality']['colorMeasurements'] ?? [];
                if (!empty($measurements)) {
                    $ws['quality'] = $measurements;
                }
            }
        }

        // Commessa gestionale
        $anno = $job && isset($job['creationDate'])
            ? date('y', strtotime($job['creationDate']))
            : date('y');
        $commessa = str_pad($jobId, 7, '0', STR_PAD_LEFT) . '-' . $anno;

        // Attivita da DB locale per questa commessa
        $attivitaDB = PrinectAttivita::where('commessa_gestionale', $commessa)
            ->orderByDesc('start_time')
            ->get();

        return view('mes.prinect_job_detail', compact(
            'jobId', 'job', 'worksteps', 'elements', 'preview',
            'milestoneMap', 'commessa', 'attivitaDB'
        ));
    }
}
