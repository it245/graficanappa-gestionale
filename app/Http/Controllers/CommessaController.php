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
                        ->with(['fasi.faseCatalogo.reparto', 'fasi.operatori', 'cliche'])
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

        // Carica l'operatore corrente (serve per sapere il reparto)
        $operatore = \App\Models\Operatore::with('reparti')->find(
            request()->attributes->get('operatore_id') ?? session('operatore_id')
        );

        // Prossime commesse: seguono l'ordine della coda operatore (per reparto)
        // 1. Prende i reparti dell'operatore
        // 2. Cerca le fasi attive (stato 0/1/2) in quei reparti, ordinate per priorità
        // 3. Raggruppa per commessa (distinct) per evitare duplicati
        $repartoIds = ($operatore && $operatore->reparti) ? $operatore->reparti->pluck('id')->toArray() : [];

        if (!empty($repartoIds)) {
            // Query sulla tabella ordine_fasi JOIN ordini:
            // - filtra per reparto dell'operatore
            // - solo fasi con stato 0, 1, 2 (nella coda, non completate)
            // - escludi fasi esterne
            // - raggruppa per commessa → MIN(priorita) = priorità più alta della commessa
            // - ordina per quella priorità
            $commesseOrdinate = OrdineFase::query()
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
                ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
                ->whereIn('ordine_fasi.stato', [0, 1, 2])
                ->where(fn($q) => $q->where('ordine_fasi.esterno', false)->orWhereNull('ordine_fasi.esterno'))
                ->where('ordini.commessa', '!=', $commessa)
                ->select('ordini.commessa')
                ->selectRaw('MIN(ordine_fasi.priorita) as min_priorita')
                ->groupBy('ordini.commessa')
                ->orderBy('min_priorita')
                ->limit(10)
                ->pluck('commessa');
        } else {
            // Fallback (owner o senza reparto): ordina per data consegna
            $commesseOrdinate = Ordine::where('commessa', '!=', $commessa)
                ->whereHas('fasi', fn($q) => $q->where('stato', '<', 3))
                ->orderBy('data_prevista_consegna')
                ->limit(10)
                ->pluck('commessa');
        }

        // Carica i modelli Ordine con conteggio fasi, poi ordina come la coda
        // unique('commessa'): una commessa può avere più ordini, ne mostriamo uno solo
        $prossime = Ordine::whereIn('commessa', $commesseOrdinate)
            ->withCount(['fasi', 'fasi as fasi_terminate_count' => fn($q) => $q->whereRaw("stato REGEXP '^[0-9]+$' AND stato >= 3 AND stato != 5")])
            ->get()
            ->unique('commessa')
            ->sortBy(fn($o) => $commesseOrdinate->search($o->commessa))
            ->values();


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

        return view('commesse.show', compact('ordine', 'ordini', 'prossime', 'operatore', 'preview'));
    }
}
