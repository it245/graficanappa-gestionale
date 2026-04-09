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
            $ocr->lang('ita+eng');
            $ocr->psm(3); // Fully automatic page segmentation
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
     * Adattato ai fornitori reali: Stratosfera, Fedrigoni, MM Board & Paper, Canon.
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

        // === FORNITORE ===
        // Nomi fornitori noti
        $fornitori = ['STRATOSFERA', 'FEDRIGONI', 'MM BOARD', 'CANON', 'TIPOLITOGRAF', 'PRO PRINT'];
        $testoUpper = mb_strtoupper($testo);
        foreach ($fornitori as $f) {
            if (str_contains($testoUpper, $f)) {
                $result['fornitore'] = $f;
                break;
            }
        }
        // Fallback: dopo "Spett.le" o "Mitt."
        if (!$result['fornitore'] && preg_match('/(?:spett\.?le|mitt\.?|mittente)[:\s]*(.+)/i', $testo, $m)) {
            $result['fornitore'] = trim($m[1]);
        }

        // === TIPO CARTA ===
        // Nomi commerciali carta (dai fornitori reali)
        $tipiCarta = [
            'ALASKA PLUS GC2 FSC', 'ALASKA PLUS GC2', 'ALASKA PLUS',
            'ALASKA WHITE', 'ALASKA',
            'SATIN PN', 'SATIN',
            'TINTORETTO', 'SYMBOL',
            'GC1 PERFORMA WHITE', 'GC1 PERFORMA', 'PERFORMA WHITE',
            'GC[12]', 'PATINATA', 'KRAFT', 'POLIPROPILENE',
            'CARTONE TESO', 'ADESIVA',
            'TONER\s+\w+',
        ];
        foreach ($tipiCarta as $pattern) {
            if (preg_match('/\b(' . $pattern . ')\b/i', $testo, $m)) {
                $result['tipo_carta'] = trim($m[1]);
                break;
            }
        }

        // === FORMATO ===
        // Formato: NNNxNNN o NNN x NNN (mm) — es. 580X800, 56x102
        if (preg_match('/\b(\d{2,4})\s*[xX×]\s*(\d{2,4})\b/', $testo, $m)) {
            $w = (int) $m[1];
            $h = (int) $m[2];
            // Se in mm (>100), converti in cm
            if ($w > 100 && $h > 100) {
                $result['formato'] = round($w / 10) . 'x' . round($h / 10);
            } else {
                $result['formato'] = $w . 'x' . $h;
            }
        }

        // === GRAMMATURA ===
        // Cerca "300g", "300 g/m", "300 gsm", "300 gr", "Grammatura 210"
        if (preg_match('/\b(\d{2,4})\s*(?:g(?:r|rammi|sm)?(?:\/m2?)?)\b/i', $testo, $m)) {
            $g = (int) $m[1];
            if ($g >= 50 && $g <= 600) {
                $result['grammatura'] = $g;
            }
        }
        // Fallback: "Grammatura NNN" o dopo "grm" "gsm"
        if (!$result['grammatura'] && preg_match('/(?:grammatura|grammage|basis\s*weight)[:\s]*(\d{2,4})/i', $testo, $m)) {
            $result['grammatura'] = (int) $m[1];
        }

        // === QUANTITA ===
        // Fogli: "1876 fogli" o colonna "Fogli" con numero
        if (preg_match('/(\d[\d\.]*)\s*(?:fogli|fg)\b/i', $testo, $m)) {
            $result['quantita'] = (int) str_replace('.', '', $m[1]);
        }
        // KG: "243 KG" — prendi il primo match
        if (!$result['quantita'] && preg_match('/\b(\d[\d\.]*)\s*(?:kg|KG)\b/', $testo, $m)) {
            $result['quantita'] = (int) str_replace('.', '', $m[1]);
        }
        // PZ/Colli: "1.000" + pz/pezzi/colli
        if (!$result['quantita'] && preg_match('/(\d[\d\.]*)\s*(?:pz|pezzi|colli|scatole)\b/i', $testo, $m)) {
            $result['quantita'] = (int) str_replace('.', '', $m[1]);
        }
        // Generico: dopo "Quantità" o "Qta"
        if (!$result['quantita'] && preg_match('/(?:quantit[àa]|qta)[:\s]*(\d[\d\.]*)/i', $testo, $m)) {
            $result['quantita'] = (int) str_replace('.', '', $m[1]);
        }

        // === LOTTO ===
        if (preg_match('/(?:lotto|lot\.?|batch)[:\s]*([A-Z0-9\-\/]+)/i', $testo, $m)) {
            $result['lotto'] = trim($m[1]);
        }
        // Codice UDC Stratosfera: P002610260407-0001
        if (!$result['lotto'] && preg_match('/(P\d{10,}[\-\d]*)/i', $testo, $m)) {
            $result['lotto'] = trim($m[1]);
        }

        return $result;
    }
}
