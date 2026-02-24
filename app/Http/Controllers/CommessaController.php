<?php

namespace App\Http\Controllers;

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Http\Services\PrinectService;

class CommessaController extends Controller
{
    public function show($commessa, PrinectService $prinect)
    {
        // Carica TUTTI gli ordini con questa commessa (fondente, latte, ecc.)
        $ordini = Ordine::where('commessa', $commessa)
                        ->with(['fasi.faseCatalogo.reparto', 'fasi.operatori'])
                        ->get();

        if ($ordini->isEmpty()) {
            abort(404);
        }

        // Primo ordine per info generali (cliente, commessa)
        $ordine = $ordini->first();

        // Raccoglie TUTTE le fasi da tutti gli ordini
        $tutteLeFasi = $ordini->flatMap(function ($o) {
            return $o->fasi->map(function ($fase) use ($o) {
                $fase->ordine_descrizione = $o->descrizione;
                return $fase;
            });
        });

        // Sostituisci la relazione fasi con tutte le fasi combinate
        $ordine->setRelation('fasi', $tutteLeFasi);

        $prossime = $ordine->priorita !== null
            ? Ordine::where('priorita', '>', $ordine->priorita)->orderBy('priorita', 'asc')->limit(5)->get()
            : Ordine::where('commessa', '!=', $commessa)->orderBy('data_prevista_consegna', 'asc')->limit(5)->get();


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

        $operatore = \App\Models\Operatore::with('reparti')->find(
            request()->attributes->get('operatore_id') ?? session('operatore_id')
        );

        return view('commesse.show', compact('ordine', 'prossime', 'operatore', 'preview'));
    }
}
