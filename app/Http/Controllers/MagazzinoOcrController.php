<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OcrBollaService;

class MagazzinoOcrController extends Controller
{
    /**
     * Processa foto bolla con OCR Tesseract.
     */
    public function processaBolla(Request $request)
    {
        $request->validate([
            'foto_bolla' => 'required|image|max:10240', // max 10MB
        ]);

        $file = $request->file('foto_bolla');
        $path = $file->store('bolle', 'public');
        $fullPath = storage_path('app/public/' . $path);

        $dati = OcrBollaService::leggi($fullPath);

        // Salva in sessione per il form carico
        $request->session()->put('ocrDati', $dati);
        $request->session()->put('ocr_foto_bolla', $path);
        $request->session()->put('ocr_raw', $dati['ocr_raw'] ?? '');

        return redirect()->route('magazzino.carico', ['op_token' => $request->get('op_token')])
            ->with('success', 'Bolla analizzata — verifica i dati estratti');
    }
}
