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
            // Sync automatico: assegna operatore e avvia fase digitale
            try {
                $syncService->sincronizza();
            } catch (\Exception $e) {
                // Non bloccare la dashboard se il sync fallisce
            }
        }

        if ($status) {
            $status['commessa'] = $this->cercaCommessa($status['stampa']['documento'] ?? null);
        }

        return view('fiery.dashboard', compact('status'));
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

        return response()->json($status);
    }

    /**
     * Diagnostica sync: mostra passo passo cosa succede
     */
    public function debugSync(FieryService $fiery, FierySyncService $syncService)
    {
        $debug = [];

        // Step 1: Status Fiery
        $status = $fiery->getServerStatus();
        $debug['1_fiery_online'] = $status ? true : false;
        $debug['1_stato'] = $status['stato'] ?? 'N/A';

        if (!$status) {
            return response()->json($debug);
        }

        // Step 2: Job in stampa
        $jobName = $status['stampa']['documento'] ?? null;
        $debug['2_job_in_stampa'] = $jobName;

        // Step 3: Estrai commessa
        $commessaCode = $syncService->estraiCommessa($jobName);
        $debug['3_commessa_estratta'] = $commessaCode;

        if (!$commessaCode) {
            $debug['3_errore'] = 'Nessun numero trovato nel nome job';
            return response()->json($debug);
        }

        // Step 4: Cerca ordine
        $ordine = \App\Models\Ordine::where('commessa', $commessaCode)->first();
        $debug['4_ordine_trovato'] = $ordine ? true : false;
        $debug['4_ordine_id'] = $ordine?->id;

        if (!$ordine) {
            return response()->json($debug);
        }

        // Step 5: Cerca operatore
        $nomeOp = config('fiery.operatore', 'Francesco Verde');
        $debug['5_operatore_config'] = $nomeOp;
        $parts = explode(' ', $nomeOp, 2);
        $operatore = \App\Models\Operatore::whereRaw('LOWER(nome) = ? AND LOWER(cognome) = ?', [
            strtolower($parts[0] ?? ''),
            strtolower($parts[1] ?? ''),
        ])->where('attivo', 1)->first();
        $debug['5_operatore_trovato'] = $operatore ? ($operatore->nome . ' ' . $operatore->cognome . ' (id=' . $operatore->id . ')') : 'NON TROVATO';

        // Step 6: Fasi digitali
        $fasiDigitali = OrdineFase::where('ordine_id', $ordine->id)
            ->whereHas('faseCatalogo', function ($q) {
                $q->where('reparto_id', 4);
            })
            ->get();
        $debug['6_fasi_digitali_count'] = $fasiDigitali->count();
        $debug['6_fasi_digitali'] = $fasiDigitali->map(function($f) {
            return [
                'id' => $f->id,
                'fase' => $f->fase,
                'stato' => $f->stato,
                'fase_catalogo_id' => $f->fase_catalogo_id,
                'operatore_id' => $f->operatore_id,
            ];
        })->toArray();

        // Step 7: TUTTE le fasi dell'ordine (per confronto)
        $tutteFasi = OrdineFase::where('ordine_id', $ordine->id)->get();
        $debug['7_tutte_fasi'] = $tutteFasi->map(function($f) {
            return [
                'id' => $f->id,
                'fase' => $f->fase,
                'stato' => $f->stato,
                'fase_catalogo_id' => $f->fase_catalogo_id,
                'reparto' => $f->reparto,
            ];
        })->toArray();

        // Step 8: Verifica fase_catalogo per UVSPOT.MGI.9M
        $uvspot = \App\Models\FasiCatalogo::where('nome', 'UVSPOT.MGI.9M')->first();
        $debug['8_uvspot_catalogo'] = $uvspot ? [
            'id' => $uvspot->id,
            'nome' => $uvspot->nome,
            'reparto_id' => $uvspot->reparto_id,
        ] : 'NON TROVATO IN CATALOGO';

        // Step 9: Prova sync
        try {
            $risultato = $syncService->sincronizza();
            $debug['9_sync_risultato'] = $risultato;
        } catch (\Exception $e) {
            $debug['9_sync_errore'] = $e->getMessage();
        }

        return response()->json($debug, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Estrae il numero dal nome job Fiery e cerca la commessa nel MES.
     * Job Fiery: "66539_Schede_BrochureMaurelliGroup_2026_33x48.pdf"
     * Commessa MES: "0066539-26" (prefisso 00, suffisso -26)
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

        // Cerca operatori assegnati alle fasi attive (stato 2 = avviata)
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
