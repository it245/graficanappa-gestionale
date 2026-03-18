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
            'nicola',
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

        // EAN precompilato solo per IC (gli altri inseriscono manualmente)
        $eanSalvato = null;

        // Trova la fase a stato 2 (avviata) dell'operatore per questo ordine
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();
        $repartiOperatore = $operatore?->reparti?->pluck('id')->toArray() ?? [];

        // Card: mostra solo se accesso dalla dashboard (con op_token)
        $faseOperatore = null;
        if ($request->query('op_token')) {
            $faseOperatore = OrdineFase::where('stato', 2)
                ->whereHas('faseCatalogo', fn($q) => $q->whereIn('reparto_id', $repartiOperatore))
                ->with(['faseCatalogo.reparto', 'operatori', 'ordine'])
                ->first();
        }

        $fasiOperatore = collect($faseOperatore ? [$faseOperatore] : []);

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

    /**
     * Lista commesse per stampa etichette (accesso libero).
     */
    public function lista(Request $request)
    {
        $filtro = $request->get('q', '');

        $query = Ordine::whereHas('fasi', fn($q) => $q->where('stato', '<', 4));

        if ($filtro) {
            $query->where(function ($q) use ($filtro) {
                $q->where('commessa', 'like', "%{$filtro}%")
                  ->orWhere('cliente_nome', 'like', "%{$filtro}%")
                  ->orWhere('descrizione', 'like', "%{$filtro}%");
            });
        }

        $commesse = $query->orderByDesc('data_registrazione')
            ->get()
            ->groupBy('commessa')
            ->map(fn($ordini) => (object)[
                'commessa' => $ordini->first()->commessa,
                'cliente_nome' => $ordini->first()->cliente_nome,
                'descrizione' => $ordini->first()->descrizione,
                'qta_richiesta' => $ordini->sum('qta_richiesta'),
                'data_prevista_consegna' => $ordini->first()->data_prevista_consegna,
                'ordini' => $ordini,
            ]);

        return view('operatore.etichette_lista', compact('commesse', 'filtro'));
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
