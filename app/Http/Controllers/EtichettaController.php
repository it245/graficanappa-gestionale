<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ordine;
use App\Models\EanProdotto;

class EtichettaController extends Controller
{
    public function show(Request $request, $ordineId)
    {
        $ordine = Ordine::findOrFail($ordineId);

        // Lotto = commessa senza zeri iniziali (es. 0066221-26 → 66221-26)
        $lotto = ltrim(explode('-', $ordine->commessa)[0], '0')
               . (str_contains($ordine->commessa, '-') ? '-' . explode('-', $ordine->commessa, 2)[1] : '');

        $cliente = $ordine->cliente_nome ?? '';
        $data = now()->format('d/m/Y');

        // Verifica se è Italiana Confetti (confronto case-insensitive)
        $isItalianaConfetti = str_contains(strtolower($cliente), 'italiana confetti');

        // Per Italiana Confetti: carica tutti gli EAN per il dropdown
        $eanProdotti = $isItalianaConfetti ? EanProdotto::orderBy('articolo')->get() : collect();

        // Per altri clienti: articolo = descrizione ordine (precompilato)
        $articoloDefault = $ordine->descrizione ?? '';

        return view('operatore.etichetta', compact(
            'ordine', 'lotto', 'cliente', 'data',
            'isItalianaConfetti', 'eanProdotti', 'articoloDefault'
        ));
    }

    public function searchEan(Request $request)
    {
        $q = $request->input('q', '');

        $risultati = EanProdotto::where('articolo', 'like', "%{$q}%")
            ->orWhere('codice_ean', 'like', "%{$q}%")
            ->orderBy('articolo')
            ->limit(30)
            ->get(['id', 'articolo', 'codice_ean']);

        return response()->json($risultati);
    }
}
