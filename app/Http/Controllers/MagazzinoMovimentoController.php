<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MagazzinoArticolo;
use App\Models\MagazzinoUbicazione;
use App\Models\MagazzinoGiacenza;
use App\Services\MagazzinoService;

class MagazzinoMovimentoController extends Controller
{
    /**
     * Form registrazione carico (bolla fornitore).
     */
    public function formCarico(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        $articoli = MagazzinoArticolo::where('attivo', true)->orderBy('descrizione')->get();
        $ubicazioni = MagazzinoUbicazione::orderBy('codice')->get();

        return view('magazzino.carico', [
            'operatore' => $operatore,
            'articoli' => $articoli,
            'ubicazioni' => $ubicazioni,
            'ocrDati' => session('ocrDati', []),
        ]);
    }

    /**
     * Registra carico da bolla.
     */
    public function registraCarico(Request $request)
    {
        $request->validate([
            'articolo_id' => 'required|exists:magazzino_articoli,id',
            'quantita' => 'required|integer|min:1',
        ]);

        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        $result = MagazzinoService::registraCarico([
            'articolo_id' => $request->articolo_id,
            'ubicazione_id' => $request->ubicazione_id,
            'quantita' => $request->quantita,
            'lotto' => $request->lotto,
            'fornitore' => $request->fornitore,
            'operatore_id' => $operatore?->id,
            'note' => $request->note,
            'foto_bolla' => $request->session()->get('ocr_foto_bolla'),
            'ocr_raw' => $request->session()->get('ocr_raw'),
        ]);

        $request->session()->forget(['ocr_foto_bolla', 'ocr_raw', 'ocrDati']);

        return redirect()->route('magazzino.etichetta.stampa', [
            'id' => $result['etichetta']->id,
            'op_token' => $request->get('op_token'),
        ])->with('success', 'Carico registrato — stampa etichetta QR');
    }

    /**
     * Form prelievo per produzione.
     */
    public function formPrelievo(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        $giacenze = MagazzinoGiacenza::with(['articolo', 'ubicazione'])
            ->where('quantita', '>', 0)
            ->get();

        return view('magazzino.prelievo', [
            'operatore' => $operatore,
            'giacenze' => $giacenze,
        ]);
    }

    /**
     * Registra prelievo (scarico per STAMPA).
     */
    public function registraPrelievo(Request $request)
    {
        $request->validate([
            'articolo_id' => 'required|exists:magazzino_articoli,id',
            'quantita' => 'required|integer|min:1',
            'commessa' => 'required|string',
        ]);

        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        try {
            MagazzinoService::registraScarico([
                'articolo_id' => $request->articolo_id,
                'ubicazione_id' => $request->ubicazione_id,
                'lotto' => $request->lotto,
                'quantita' => $request->quantita,
                'commessa' => $request->commessa,
                'fase' => $request->fase ?? 'STAMPA',
                'operatore_id' => $operatore?->id,
                'note' => $request->note,
            ]);

            return redirect()->route('magazzino.dashboard', ['op_token' => $request->get('op_token')])
                ->with('success', 'Prelievo registrato');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
