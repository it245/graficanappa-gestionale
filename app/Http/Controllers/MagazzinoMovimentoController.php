<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MagazzinoArticolo;
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

        return view('magazzino.carico', [
            'operatore' => $operatore,
            'articoli' => $articoli,
            'ocrDati' => session('ocrDati', []),
        ]);
    }

    /**
     * Registra carico da bolla.
     */
    public function registraCarico(Request $request)
    {
        $request->validate([
            'quantita' => 'required|numeric|min:0.01',
        ]);

        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        // Se non selezionato un articolo esistente, creane uno nuovo
        $articoloId = $request->articolo_id;
        if (!$articoloId) {
            $tipoCarta = trim($request->categoria ?? '');
            $formato = trim($request->formato ?? '');
            $grammatura = $request->grammatura ? (int) $request->grammatura : null;

            if (!$tipoCarta) {
                return back()->withInput()->with('error', 'Seleziona un articolo esistente o compila il tipo carta.');
            }

            // Genera codice da tipo carta + formato + grammatura
            $codice = strtoupper(preg_replace('/[^A-Z0-9]/', '', $tipoCarta));
            if ($formato) $codice .= '.' . str_replace('x', 'X', $formato);
            if ($grammatura) $codice .= '.' . $grammatura;

            $descrizione = $tipoCarta;
            if ($formato) $descrizione .= ' ' . $formato;
            if ($grammatura) $descrizione .= ' ' . $grammatura . 'g';

            $articolo = MagazzinoArticolo::firstOrCreate(
                ['codice' => $codice],
                [
                    'descrizione' => $descrizione,
                    'categoria' => $tipoCarta,
                    'formato' => $formato ?: null,
                    'grammatura' => $grammatura,
                    'fornitore' => $request->fornitore,
                ]
            );
            $articoloId = $articolo->id;
        }

        $result = MagazzinoService::registraCarico([
            'articolo_id' => $articoloId,
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
     * Form reso (rientro fogli avanzati).
     */
    public function formReso(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        $articoli = MagazzinoArticolo::where('attivo', true)->orderBy('descrizione')->get();

        return view('magazzino.reso', [
            'operatore' => $operatore,
            'articoli' => $articoli,
        ]);
    }

    /**
     * Registra reso.
     */
    public function registraReso(Request $request)
    {
        $request->validate([
            'articolo_id' => 'required|exists:magazzino_articoli,id',
            'quantita' => 'required|numeric|min:0.01',
            'commessa' => 'nullable|string',
        ]);

        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        MagazzinoService::registraReso([
            'articolo_id' => $request->articolo_id,
            'quantita' => $request->quantita,
            'lotto' => $request->lotto ?: null,
            'commessa' => $request->commessa,
            'operatore_id' => $operatore?->id,
            'note' => $request->note,
        ]);

        return redirect()->route('magazzino.dashboard', ['op_token' => $request->get('op_token')])
            ->with('success', 'Reso registrato');
    }

    /**
     * Form rettifica inventariale.
     */
    public function formRettifica(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        $giacenze = MagazzinoGiacenza::with(['articolo'])
            ->orderBy('articolo_id')
            ->get();

        return view('magazzino.rettifica', [
            'operatore' => $operatore,
            'giacenze' => $giacenze,
        ]);
    }

    /**
     * Registra rettifica.
     */
    public function registraRettifica(Request $request)
    {
        $request->validate([
            'giacenza_id' => 'required|exists:magazzino_giacenze,id',
            'nuova_quantita' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        try {
            MagazzinoService::rettifica(
                $request->giacenza_id,
                $request->nuova_quantita,
                $operatore?->id,
                $request->note
            );

            return redirect()->route('magazzino.giacenze', ['op_token' => $request->get('op_token')])
                ->with('success', 'Rettifica registrata');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Registra prelievo (scarico per STAMPA).
     */
    public function registraPrelievo(Request $request)
    {
        $request->validate([
            'articolo_id' => 'required|exists:magazzino_articoli,id',
            'quantita' => 'required|numeric|min:0.01',
            'commessa' => 'required|string',
        ]);

        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        try {
            MagazzinoService::registraScarico([
                'articolo_id' => $request->articolo_id,
                'ubicazione_id' => $request->ubicazione_id ?: null,
                'lotto' => $request->lotto ?: null,
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
