<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MagazzinoService;
use App\Models\MagazzinoGiacenza;

class MagazzinoScannerController extends Controller
{
    /**
     * Scanner QR (pagina fullscreen con fotocamera).
     */
    public function scanner(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        return view('magazzino.scanner', [
            'operatore' => $operatore,
            'modalita' => 'magazzino', // gestione completa (carico/scarico/reso)
        ]);
    }

    /**
     * Lookup QR code — restituisce dati bancale in JSON.
     */
    public function lookup(Request $request)
    {
        $request->validate(['qr' => 'required|string']);

        $etichetta = MagazzinoService::lookupQr($request->qr);

        if (!$etichetta) {
            return response()->json(['found' => false, 'message' => 'QR non riconosciuto'], 404);
        }

        $giacenza = $etichetta->giacenza;

        return response()->json([
            'found' => true,
            'etichetta_id' => $etichetta->id,
            'articolo' => [
                'id' => $etichetta->articolo->id,
                'codice' => $etichetta->articolo->codice,
                'descrizione' => $etichetta->articolo->descrizione,
                'tipo_carta' => $etichetta->articolo->tipo_carta,
                'formato' => $etichetta->articolo->formato,
                'grammatura' => $etichetta->articolo->grammatura,
            ],
            'ubicazione' => $etichetta->ubicazione ? [
                'id' => $etichetta->ubicazione->id,
                'codice' => $etichetta->ubicazione->codice,
            ] : null,
            'lotto' => $etichetta->lotto,
            'giacenza' => $giacenza ? $giacenza->quantita : 0,
            'quantita_iniziale' => $etichetta->quantita_iniziale,
        ]);
    }

    /**
     * Scanner QR per operatore (prelievo dalla dashboard operatore).
     */
    public function scannerOperatore(Request $request)
    {
        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        return view('magazzino.scanner', [
            'operatore' => $operatore,
            'modalita' => 'operatore', // solo prelievo
        ]);
    }

    /**
     * Prelievo rapido da scansione QR (operatore).
     */
    public function prelievoOperatore(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string',
            'quantita' => 'required|integer|min:1',
            'commessa' => 'required|string',
        ]);

        $operatore = $request->attributes->get('operatore') ?? auth()->guard('operatore')->user();

        $etichetta = MagazzinoService::lookupQr($request->qr_code);
        if (!$etichetta) {
            return response()->json(['success' => false, 'message' => 'QR non riconosciuto'], 404);
        }

        try {
            MagazzinoService::registraScarico([
                'articolo_id' => $etichetta->articolo_id,
                'ubicazione_id' => $etichetta->ubicazione_id,
                'lotto' => $etichetta->lotto,
                'quantita' => $request->quantita,
                'commessa' => $request->commessa,
                'fase' => 'STAMPA',
                'operatore_id' => $operatore?->id,
            ]);

            return response()->json(['success' => true, 'message' => 'Prelievo registrato']);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
