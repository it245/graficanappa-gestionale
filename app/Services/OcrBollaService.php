<?php

namespace App\Services;

use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\Log;

class OcrBollaService
{
    /**
     * Legge una foto di bolla con Tesseract OCR ed estrae i dati.
     */
    public static function leggi(string $imagePath): array
    {
        try {
            $ocr = new TesseractOCR($imagePath);
            $ocr->executable(env('TESSERACT_PATH', 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe'));
            $ocr->lang('ita');
            $ocr->psm(6); // Assume un blocco di testo uniforme
            $testo = $ocr->run();

            Log::info('OCR Bolla - testo estratto', ['path' => $imagePath, 'chars' => strlen($testo)]);

            $dati = self::parseBolla($testo);
            $dati['ocr_raw'] = $testo;

            return $dati;
        } catch (\Exception $e) {
            Log::error('OCR Bolla - errore', ['error' => $e->getMessage()]);
            return [
                'ocr_raw' => '',
                'fornitore' => '',
                'quantita' => null,
                'grammatura' => null,
                'formato' => '',
                'lotto' => '',
                'tipo_carta' => '',
                'errore' => $e->getMessage(),
            ];
        }
    }

    /**
     * Estrae i dati strutturati dal testo OCR di una bolla.
     */
    private static function parseBolla(string $testo): array
    {
        $result = [
            'fornitore' => '',
            'quantita' => null,
            'grammatura' => null,
            'formato' => '',
            'lotto' => '',
            'tipo_carta' => '',
        ];

        // Fornitore: dopo "Spett.le" o "Mitt." o prima riga significativa
        if (preg_match('/(?:spett\.?le|mitt\.?|mittente)[:\s]*(.+)/i', $testo, $m)) {
            $result['fornitore'] = trim($m[1]);
        }

        // Quantita: numero + fg/fogli/mq/kg/pz
        if (preg_match('/(\d[\d\.]*)\s*(?:fg|fogli|mq|kg|pz)/i', $testo, $m)) {
            $result['quantita'] = (int) str_replace('.', '', $m[1]);
        }

        // Grammatura: numero + g/gr/grammi/gsm
        if (preg_match('/(\d{2,4})\s*(?:g(?:r|rammi)?|gsm)\b/i', $testo, $m)) {
            $result['grammatura'] = (int) $m[1];
        }

        // Formato: NNxNN o NN x NN (cm)
        if (preg_match('/(\d{2,3})\s*[xX×]\s*(\d{2,3})/', $testo, $m)) {
            $result['formato'] = $m[1] . 'x' . $m[2];
        }

        // Lotto: dopo "Lotto" o "L." o "Lot."
        if (preg_match('/(?:lotto|lot\.?|l\.)\s*[:\s]*([A-Z0-9\-]+)/i', $testo, $m)) {
            $result['lotto'] = trim($m[1]);
        }

        // Tipo carta: GC1, GC2, patinata, kraft, polipropilene, ecc.
        if (preg_match('/\b(GC[12]|patinata|kraft|polipropilene|cartone\s*teso|adesiva|performa\s*white?)\b/i', $testo, $m)) {
            $result['tipo_carta'] = trim($m[1]);
        }

        return $result;
    }
}
