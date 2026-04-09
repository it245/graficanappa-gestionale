<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OcrBollaService
{
    /**
     * Legge una foto di bolla con Gemini Vision ed estrae i dati strutturati.
     */
    public static function leggi(string $imagePath): array
    {
        try {
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';

            $apiKey = env('GEMINI_API_KEY');
            if (!$apiKey) {
                throw new \RuntimeException('GEMINI_API_KEY non configurata nel .env');
            }

            $response = Http::withOptions(['verify' => false])->timeout(30)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => self::getPrompt(),
                                ],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => $imageData,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 1024,
                    ],
                ]
            );

            if (!$response->successful()) {
                throw new \RuntimeException('Gemini API errore: ' . $response->status() . ' ' . $response->body());
            }

            $body = $response->json();
            $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';

            Log::info('OCR Bolla Gemini - risposta', ['chars' => strlen($text)]);

            $dati = self::parseGeminiResponse($text);
            $dati['ocr_raw'] = $text;

            return $dati;
        } catch (\Exception $e) {
            Log::error('OCR Bolla Gemini - errore', ['error' => $e->getMessage()]);
            return [
                'ocr_raw' => 'Errore: ' . $e->getMessage(),
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
     * Prompt per Gemini: estrai dati strutturati dalla bolla.
     */
    private static function getPrompt(): string
    {
        return <<<'PROMPT'
Sei un sistema OCR per una tipografia. Analizza questa foto di una bolla/DDT di un fornitore di carta e estrai i seguenti dati. Rispondi SOLO in formato JSON, senza markdown, senza spiegazioni.

Campi da estrarre:
- "fornitore": nome del fornitore/mittente (es. "Stratosfera", "Fedrigoni", "MM Board & Paper")
- "tipo_carta": nome commerciale della carta (es. "ALASKA PLUS GC2 FSC", "SATIN PN", "Symbol Card")
- "formato": formato in cm, scritto come LxH (es. "58x80", "70x100"). Se trovi mm, converti in cm.
- "grammatura": grammatura in g/m2, solo il numero (es. 270, 300, 350)
- "quantita": quantita totale in fogli (cerca "NR", "fogli", "fg"). Se trovi solo kg, metti i kg.
- "um": unita di misura della quantita ("fg" per fogli, "kg" per chilogrammi)
- "lotto": numero di lotto se presente
- "n_bancali": numero di bancali/pallet se presente
- "note": altre informazioni utili (numero bolla, data, commessa riferimento)

Se un campo non e' presente o non e' leggibile, usa stringa vuota "" o null per i numeri.

Rispondi SOLO con il JSON, esempio:
{"fornitore":"Stratosfera","tipo_carta":"ALASKA PLUS GC2 FSC","formato":"58x80","grammatura":270,"quantita":13098,"um":"fg","lotto":"","n_bancali":8,"note":"Bolla 169 del 7/04/26"}
PROMPT;
    }

    /**
     * Parsa la risposta JSON di Gemini.
     */
    private static function parseGeminiResponse(string $text): array
    {
        $result = [
            'fornitore' => '',
            'quantita' => null,
            'grammatura' => null,
            'formato' => '',
            'lotto' => '',
            'tipo_carta' => '',
        ];

        // Estrai JSON dalla risposta (potrebbe avere testo extra)
        if (preg_match('/\{[^{}]*\}/', $text, $m)) {
            $json = json_decode($m[0], true);
            if ($json) {
                $result['fornitore'] = $json['fornitore'] ?? '';
                $result['tipo_carta'] = $json['tipo_carta'] ?? '';
                $result['formato'] = $json['formato'] ?? '';
                $result['grammatura'] = !empty($json['grammatura']) ? (int) $json['grammatura'] : null;
                $result['lotto'] = $json['lotto'] ?? '';

                // Quantita
                if (!empty($json['quantita'])) {
                    $result['quantita'] = (int) $json['quantita'];
                }
            }
        }

        return $result;
    }
}
