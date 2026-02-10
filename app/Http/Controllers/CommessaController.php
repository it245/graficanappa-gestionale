<?php

namespace App\Http\Controllers;

use App\Models\Ordine;

class CommessaController extends Controller
{
    public function show($commessa)
    {
        $ordine = Ordine::with(['fasi.faseCatalogo', 'fasi.operatori'])
            ->where('commessa', $commessa)
            ->firstOrFail();

        // Prossime 5 commesse con priorità minore
        $prossime = Ordine::where('priorita', '>', $ordine->priorita)
            ->orderBy('priorita')
            ->limit(5)
            ->get();

        // Operatore loggato
        $operatore = auth()->guard('operatore')->user();

        // ID dei reparti dell'operatore
        $repartiOperatore = $operatore ? $operatore->reparti->pluck('id')->toArray() : [];

        return view('commesse.show', compact('ordine', 'prossime', 'operatore', 'repartiOperatore'));
    }
}