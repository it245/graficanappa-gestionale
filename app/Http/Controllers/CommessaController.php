<?php

namespace App\Http\Controllers;

use App\Models\Ordine;
use App\Models\OrdineFase;

class CommessaController extends Controller
{
    public function show($commessa)
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

        $prossime = Ordine::where('priorita', '>', $ordine->priorita)
                          ->orderBy('priorita', 'asc')
                          ->limit(5)
                          ->get();

        $operatore = \App\Models\Operatore::with('reparti')->find(session('operatore_id'));

        return view('commesse.show', compact('ordine', 'prossime', 'operatore'));
    }
}
