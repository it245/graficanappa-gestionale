<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ordine;
use App\Models\OrdineFase;
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

        // Verifica se è Tifata Plastica
        $isTifataPlastica = str_contains(strtolower($cliente), 'tifata');

        // Clienti con etichetta semplificata (solo Pz x cassa, Lotto, Data)
        $clientiSemplici = [
            'comprof', 'horecapp', 'promocart', 'bpack communication',
            'ariagrafica', 'studio w', 'studioesse', 'studio esse',
            'mundo', 'booster', 'openmind', 'russart', 'grafiche mercurio',
            'medspa', 'full pack', 'iltex', 'fashion color', 'advertage',
        ];
        $clienteLower = strtolower($cliente);
        $isSimpleLabel = false;
        foreach ($clientiSemplici as $cs) {
            if (str_contains($clienteLower, $cs)) {
                $isSimpleLabel = true;
                break;
            }
        }

        // Per Italiana Confetti: carica tutti gli EAN per il dropdown
        $eanProdotti = $isItalianaConfetti ? EanProdotto::orderBy('articolo')->get() : collect();

        // Per altri clienti: articolo = descrizione ordine (precompilato)
        $articoloDefault = $ordine->descrizione ?? '';

        // Cerca EAN già salvato per questo articolo (non-IC)
        $eanSalvato = null;
        if (!$isItalianaConfetti && $articoloDefault) {
            $eanSalvato = EanProdotto::where('articolo', $articoloDefault)->first();
        }

        // Trova TUTTE le fasi dell'operatore per questo ordine (per le card gestione fase)
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();
        $repartiOperatore = $operatore?->reparti?->pluck('id')->toArray() ?? [];

        $fasiOperatore = OrdineFase::where('ordine_id', $ordine->id)
            ->whereHas('faseCatalogo', fn($q) => $q->whereIn('reparto_id', $repartiOperatore))
            ->with(['faseCatalogo.reparto', 'operatori'])
            ->orderBy('id')
            ->get();

        // Retrocompatibilità: prima fase per variabili singole
        $faseOperatore = $fasiOperatore->first();

        // Note fasi successive
        $noteFS = $ordine->note_fasi_successive ?? '';
        $righeFS = $noteFS ? json_decode($noteFS, true) : [];
        if (!is_array($righeFS)) $righeFS = [];

        return view('operatore.etichetta', compact(
            'ordine', 'lotto', 'cliente', 'data',
            'isItalianaConfetti', 'isSimpleLabel', 'isTifataPlastica', 'eanProdotti', 'articoloDefault', 'eanSalvato',
            'fasiOperatore', 'faseOperatore', 'operatore', 'righeFS'
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

    public function salvaEan(Request $request)
    {
        $articolo = trim($request->input('articolo', ''));
        $codiceEan = trim($request->input('codice_ean', ''));

        if (!$articolo || !$codiceEan) {
            return response()->json(['ok' => false, 'msg' => 'Articolo e codice EAN richiesti'], 422);
        }

        EanProdotto::updateOrCreate(
            ['articolo' => $articolo],
            ['codice_ean' => $codiceEan]
        );

        return response()->json(['ok' => true]);
    }
}
