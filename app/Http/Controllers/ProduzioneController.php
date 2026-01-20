<?php

namespace App\Http\Controllers;

use App\Models\OrdineFase;
use App\Models\Operatore;
use Illuminate\Http\Request;

class ProduzioneController extends Controller
{

public function index()
    {
        // Prendi tutti gli ordini e relative fasi
        $ordini = Ordine::with('fasi')->get();

        return view('produzione.index', compact('ordini'));
    }
    // ▶️ Avvia una fase
    public function avvia(Request $request)
    {
        $request->validate([
            'fase_id' => 'required|exists:ordine_fasi,id',
            'operatore_id' => 'required|exists:operatori,id',
        ]);

        $fase = OrdineFase::findOrFail($request->fase_id);
        $operatore = Operatore::findOrFail($request->operatore_id);

        $fase->avvia($operatore);

        return response()->json([
            'success' => 'Fase avviata correttamente'
        ]);
    }

    // ▶️ Aggiungi produzione
    public function produzione(Request $request)
    {
        $request->validate([
            'fase_id' => 'required|exists:ordine_fasi,id',
            'quantita' => 'required|numeric|min:1',
        ]);

        $fase = OrdineFase::findOrFail($request->fase_id);

        $fase->aggiungiProduzione((int) $request->quantita);

        return response()->json([
            'success' => 'Produzione aggiornata'
        ]);
    }

    // ▶️ Termina fase
    public function termina(Request $request)
    {
        $request->validate([
            'fase_id' => 'required|exists:ordine_fasi,id',
        ]);

        $fase = OrdineFase::findOrFail($request->fase_id);

        $fase->termina();

        return response()->json([
            'success' => 'Fase terminata'
        ]);
    }
}
