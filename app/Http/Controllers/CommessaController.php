<?php

namespace App\Http\Controllers;

use App\Models\Ordine;

class CommessaController extends Controller
{
  public function show($commessa)
{
    $ordine = Ordine::where('commessa', $commessa)
                     ->with(['fasi.faseCatalogo', 'fasi.operatori'])
                     ->firstOrFail();

    $prossime = Ordine::where('priorita', '>', $ordine->priorita)
                      ->orderBy('priorita', 'asc')
                      ->limit(5)
                      ->get();

    $operatore = auth('operatore')->user()->load('reparti'); // 🔹 importante

    return view('commesse.show', compact('ordine', 'prossime', 'operatore'));
}
}