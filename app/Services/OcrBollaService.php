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
            $ocr->psm(3);
            $testo = $ocr->run();

            Log::info('OCR Bolla - testo estratto', ['path' => $imagePath, 'chars' => strlen($testo)]);

            $dati = self::parseBolla($testo);
            $dati['ocr_raw'] = $testo;

            return $dati;
        } catch (\Exception $e) {
            Log::error('OCR Bolla - errore', ['error' => $e->getMessage()]);
            return [
                'ocr_raw' => 'Errore: ' . $e->getMessage(),
                'fornitore' => '',
                'quantita' => null,
                'grammatura' => null,
                'formato' => '',
                'lotto' => '',
                'categoria' => '',
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
            'categoria' => '',
        ];

        // === FORNITORE ===
        $fornitori = ['STRATOSFERA', 'FEDRIGONI', 'MM BOARD', 'CANON', 'TIPOLITOGRAF', 'PRO PRINT'];
        $testoUpper = mb_strtoupper($testo);
        foreach ($fornitori as $f) {
            if (str_contains($testoUpper, $f)) {
                $result['fornitore'] = $f;
                break;
            }
        }
        if (!$result['fornitore'] && preg_match('/(?:spett\.?le|mitt\.?|mittente)[:\s]*(.+)/i', $testo, $m)) {
            $result['fornitore'] = trim($m[1]);
        }

        // === TIPO CARTA ===
        $tipiCarta = [
            'ALASKA PLUS GC2 FSC', 'ALASKA PLUS GC2', 'ALASKA PLUS',
            'ALASKA WHITE', 'ALASKA',
            'SATIN PN', 'SATIN',
            'TINTORETTO', 'SYMBOL',
            'GC1 PERFORMA WHITE', 'GC1 PERFORMA', 'PERFORMA WHITE',
            'GC[12]', 'PATINATA', 'KRAFT', 'POLIPROPILENE',
            'CARTONE TESO', 'ADESIVA',
        ];
        foreach ($tipiCarta as $pattern) {
            if (preg_match('/\b(' . $pattern . ')\b/i', $testo, $m)) {
                $result['categoria'] = trim($m[1]);
                break;
            }
        }

        // === FORMATO ===
        if (preg_match('/\b(\d{2,4})\s*[xX×]\s*(\d{2,4})\b/', $testo, $m)) {
            $w = (int) $m[1];
            $h = (int) $m[2];
            if ($w > 100 && $h > 100) {
                $result['formato'] = round($w / 10) . 'x' . round($h / 10);
            } else {
                $result['formato'] = $w . 'x' . $h;
            }
        }

        // === GRAMMATURA ===
        if (preg_match('/(?:grammatura|grammage|basis\s*weight)[:\s]*(\d{2,4})/i', $testo, $m)) {
            $result['grammatura'] = (int) $m[1];
        }
        if (!$result['grammatura'] && preg_match('/\b(\d{2,4})\s*(?:g(?:r|rammi|sm)?(?:\/m2?)?)\b/i', $testo, $m)) {
            $g = (int) $m[1];
            if ($g >= 50 && $g <= 600) {
                $result['grammatura'] = $g;
            }
        }

        // === QUANTITA ===
        if (preg_match('/\bNR\s+([\d\.,]+)/i', $testo, $m)) {
            $result['quantita'] = (int) preg_replace('/[^\d]/', '', $m[1]);
        }
        if (!$result['quantita'] && preg_match('/([\d\.,]+)\s*(?:fogli|fg)\b/i', $testo, $m)) {
            $result['quantita'] = (int) preg_replace('/[^\d]/', '', $m[1]);
        }
        if (!$result['quantita'] && preg_match('/\b([\d\.,]+)\s*(?:kg|KG)\b/', $testo, $m)) {
            $result['quantita'] = (int) preg_replace('/[^\d]/', '', $m[1]);
        }
        if (!$result['quantita'] && preg_match('/([\d\.,]+)\s*(?:pz|pezzi|colli|scatole)\b/i', $testo, $m)) {
            $result['quantita'] = (int) preg_replace('/[^\d]/', '', $m[1]);
        }
        if (!$result['quantita'] && preg_match('/(?:quantit[àa]|qta)[:\s]*([\d\.,]+)/i', $testo, $m)) {
            $result['quantita'] = (int) preg_replace('/[^\d]/', '', $m[1]);
        }

        // === LOTTO ===
        if (preg_match('/(?:lotto|lot\.?|batch)[:\s]*([A-Z0-9\-\/]+)/i', $testo, $m)) {
            $result['lotto'] = trim($m[1]);
        }
        if (!$result['lotto'] && preg_match('/(P\d{10,}[\-\d]*)/i', $testo, $m)) {
            $result['lotto'] = trim($m[1]);
        }

        return $result;
    }
}
