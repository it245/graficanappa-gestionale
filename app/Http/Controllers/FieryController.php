<?php

namespace App\Http\Controllers;

use App\Http\Services\FieryService;
use App\Http\Services\FierySyncService;
use App\Models\Ordine;
use App\Models\OrdineFase;
use Illuminate\Http\Request;

class FieryController extends Controller
{
    public function index(FieryService $fiery, FierySyncService $syncService)
    {
        $status = $fiery->getServerStatus();

        if ($status && ($status['stampa']['documento'] ?? null)) {
            try {
                $syncService->sincronizza();
            } catch (\Exception $e) {
                // Non bloccare la dashboard se il sync fallisce
            }
        }

        if ($status) {
            $status['commessa'] = $this->cercaCommessa($status['stampa']['documento'] ?? null);
        }

        // Job list da API v5
        $jobs = $fiery->getJobs();
        $jobData = $this->organizzaJobs($jobs);

        return view('fiery.dashboard', compact('status', 'jobData'));
    }

    public function statusJson(FieryService $fiery, FierySyncService $syncService)
    {
        $status = $fiery->getServerStatus();

        if (!$status) {
            return response()->json([
                'online' => false,
                'stato' => 'offline',
                'avviso' => 'Fiery non raggiungibile',
            ]);
        }

        if ($status['stampa']['documento'] ?? null) {
            try {
                $syncService->sincronizza();
            } catch (\Exception $e) {
                // Non bloccare il polling
            }
        }

        $status['commessa'] = $this->cercaCommessa($status['stampa']['documento'] ?? null);

        // Job list da API v5
        $jobs = app(FieryService::class)->getJobs();
        $status['jobs'] = $this->organizzaJobs($jobs);

        return response()->json($status);
    }

    /**
     * Organizza i job in categorie per la dashboard
     */
    private function organizzaJobs(?array $jobs): array
    {
        if (!$jobs) {
            return ['printing' => null, 'queue' => [], 'completed' => [], 'total' => 0];
        }

        $printing = null;
        $queue = [];
        $completed = [];

        foreach ($jobs as $job) {
            // Aggiungi info commessa dal MES
            $job['mes'] = null;
            if ($job['commessa']) {
                $ordine = Ordine::where('commessa', $job['commessa'])->first();
                if ($ordine) {
                    $job['mes'] = [
                        'commessa' => $ordine->commessa,
                        'cliente' => $ordine->cliente_nome,
                        'cod_art' => $ordine->cod_art,
                    ];
                }
            }

            if ($job['state'] === 'printing') {
                $printing = $job;
            } elseif (in_array($job['state'], ['done spooling', 'waiting', 'processing', 'ripping'])) {
                $queue[] = $job;
            } elseif ($job['state'] === 'completed') {
                $completed[] = $job;
            }
        }

        // Completati: ultimi 15 (i più recenti per data)
        $completed = array_slice(array_reverse($completed), 0, 15);

        return [
            'printing' => $printing,
            'queue' => $queue,
            'completed' => $completed,
            'total' => count($jobs),
        ];
    }

    /**
     * Diagnostica sync
     */
    public function debugSync(FieryService $fiery, FierySyncService $syncService)
    {
        $debug = [];

        $status = $fiery->getServerStatus();
        $debug['1_fiery_online'] = $status ? true : false;
        $debug['1_stato'] = $status['stato'] ?? 'N/A';

        if (!$status) {
            return response()->json($debug);
        }

        $jobName = $status['stampa']['documento'] ?? null;
        $debug['2_job_in_stampa'] = $jobName;

        $commessaCode = $syncService->estraiCommessa($jobName);
        $debug['3_commessa_estratta'] = $commessaCode;

        if (!$commessaCode) {
            $debug['3_errore'] = 'Nessun numero trovato nel nome job';
            return response()->json($debug);
        }

        $ordini = Ordine::where('commessa', $commessaCode)->get();
        $debug['4_ordini_count'] = $ordini->count();
        $debug['4_ordini'] = $ordini->map(function($o) use ($syncService) {
            return [
                'id' => $o->id,
                'cod_art' => $o->cod_art,
                'descrizione' => substr($o->descrizione, 0, 60),
                'cod_carta' => $o->cod_carta,
                'formato_digitale' => $syncService->isFormatoDigitale($o->cod_carta),
            ];
        })->toArray();

        if ($ordini->isEmpty()) {
            return response()->json($debug);
        }

        $nomeOp = config('fiery.operatore', 'Francesco Verde');
        $debug['5_operatore_config'] = $nomeOp;
        $parts = explode(' ', $nomeOp, 2);
        $operatore = \App\Models\Operatore::whereRaw('LOWER(nome) = ? AND LOWER(cognome) = ?', [
            strtolower($parts[0] ?? ''),
            strtolower($parts[1] ?? ''),
        ])->where('attivo', 1)->first();
        $debug['5_operatore_trovato'] = $operatore ? ($operatore->nome . ' ' . $operatore->cognome . ' (id=' . $operatore->id . ')') : 'NON TROVATO';

        $tutteFasiDigitali = collect();
        foreach ($ordini as $ordine) {
            $fasi = OrdineFase::where('ordine_id', $ordine->id)
                ->where(function ($q) use ($ordine, $syncService) {
                    $q->whereHas('faseCatalogo', function ($sub) {
                        $sub->where('reparto_id', 4);
                    });
                    if ($syncService->isFormatoDigitale($ordine->cod_carta)) {
                        $q->orWhere('fase', 'STAMPA');
                    }
                })
                ->get();
            $tutteFasiDigitali = $tutteFasiDigitali->merge($fasi);
        }
        $debug['6_fasi_digitali_count'] = $tutteFasiDigitali->count();
        $debug['6_fasi_digitali'] = $tutteFasiDigitali->map(function($f) {
            return [
                'id' => $f->id,
                'fase' => $f->fase,
                'stato' => $f->stato,
                'ordine_id' => $f->ordine_id,
            ];
        })->toArray();

        try {
            $risultato = $syncService->sincronizza();
            $debug['9_sync_risultato'] = $risultato;
        } catch (\Exception $e) {
            $debug['9_sync_errore'] = $e->getMessage();
        }

        return response()->json($debug, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Estrae il numero dal nome job e cerca la commessa nel MES.
     */
    private function cercaCommessa(?string $jobName): ?array
    {
        if (!$jobName) return null;

        if (!preg_match('/^(\d+)_/', $jobName, $matches)) {
            return null;
        }

        $numero = $matches[1];
        $commessaCode = '00' . $numero . '-26';

        $ordine = Ordine::where('commessa', $commessaCode)->first();
        if (!$ordine) return null;

        $fasiAttive = OrdineFase::where('ordine_id', $ordine->id)
            ->where('stato', 2)
            ->with(['operatori' => function($q) {
                $q->select('operatori.id', 'operatori.nome');
            }])
            ->get();

        $operatori = $fasiAttive->flatMap(function($fase) {
            return $fase->operatori->pluck('nome');
        })->unique()->values()->toArray();

        return [
            'commessa' => $ordine->commessa,
            'cliente' => $ordine->cliente_nome,
            'descrizione' => $ordine->descrizione,
            'operatori' => $operatori,
        ];
    }
}
