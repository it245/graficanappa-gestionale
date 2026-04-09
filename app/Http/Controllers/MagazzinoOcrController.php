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
            'foto_bolla' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        $file = $request->file('foto_bolla');
        $path = $file->store('bolle'); // storage privato, NON public
        $fullPath = storage_path('app/' . $path);

        $dati = OcrBollaService::leggi($fullPath);

        // Salva in sessione per il form carico
        $request->session()->put('ocrDati', $dati);
        $request->session()->put('ocr_foto_bolla', $path);
        $request->session()->put('ocr_raw', $dati['ocr_raw'] ?? '');

        return redirect()->route('magazzino.carico', ['op_token' => $request->get('op_token')])
            ->with('success', 'Bolla analizzata — verifica i dati estratti');
    }
}
