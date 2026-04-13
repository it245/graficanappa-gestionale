<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OcrBollaService;
use Illuminate\Support\Facades\Log;

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
        $fullPath = storage_path('app/private/' . $path);

        try {
            $dati = OcrBollaService::leggi($fullPath);
        } catch (\Exception $e) {
            Log::error('OCR Bolla errore nel controller', ['error' => $e->getMessage()]);
            $dati = [
                'ocr_raw' => '',
                'fornitore' => '',
                'quantita' => null,
                'grammatura' => null,
                'formato' => '',
                'lotto' => '',
                'tipo_carta' => '',
            ];
        }

        // Salva in sessione per il form carico
        $request->session()->put('ocrDati', $dati);
        $request->session()->put('ocr_foto_bolla', $path);
        $request->session()->put('ocr_raw', $dati['ocr_raw'] ?? '');

        $msg = !empty($dati['errore'])
            ? 'OCR non disponibile — compila i dati manualmente'
            : 'Bolla analizzata — verifica i dati estratti';

        return redirect()->route('magazzino.carico', ['op_token' => $request->get('op_token')])
            ->with(!empty($dati['errore']) ? 'error' : 'success', $msg);
    }
}
