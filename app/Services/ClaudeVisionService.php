<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integrazione API Anthropic Claude (Vision).
 * Analizza foto bolle fornitore e restituisce dati strutturati.
 */
class ClaudeVisionService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';
    private const DEFAULT_MODEL = 'claude-haiku-4-5-20251001';
    private const DEFAULT_MAX_TOKENS = 1024;

    /**
     * Estrae dati strutturati da foto bolla fornitore.
     *
     * @param string $imagePath Percorso assoluto file immagine
     * @return array Dati estratti + ocr_raw per audit
     * @throws \Exception se API non raggiungibile o risposta invalida
     */
    public static function estraiBolla(string $imagePath): array
    {
        $apiKey = env('ANTHROPIC_API_KEY');
        if (!$apiKey) {
            throw new \Exception('ANTHROPIC_API_KEY non configurata');
        }

        if (!file_exists($imagePath)) {
            throw new \Exception("File immagine non trovato: {$imagePath}");
        }

        $mediaType = mime_content_type($imagePath);
        if (!in_array($mediaType, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'])) {
            throw new \Exception("Formato immagine non supportato: {$mediaType}");
        }

        $imageData = base64_encode(file_get_contents($imagePath));

        $prompt = <<<PROMPT
Sei un assistente specializzato nell'analisi di bolle di trasporto cartacee italiane di fornitori di carta per stamperie.

Analizza questa foto di bolla e restituisci ESCLUSIVAMENTE un oggetto JSON con questi campi (stringhe vuote o null se non presenti):

{
  "fornitore": "ragione sociale fornitore",
  "numero_bolla": "numero documento",
  "data_bolla": "gg/mm/aaaa",
  "categoria": "GC1|GC2|Patinata|Uncoated|Cartoncino|Altro",
  "descrizione_carta": "descrizione tecnica completa",
  "grammatura": 300,
  "formato": "56x102 oppure larghezzaxaltezza in cm",
  "quantita": 2000,
  "unita_misura": "fg|kg|mq",
  "lotto": "codice lotto produzione",
  "note": "eventuali note rilevanti"
}

REGOLE:
- Rispondi SOLO con il JSON, nessun testo aggiuntivo, nessun markdown
- Numeri come numeri (non stringhe): grammatura, quantita
- Se un campo non è leggibile metti stringa vuota o null
- Se la foto non è una bolla valida, ritorna JSON con fornitore: "" e note: "foto non riconosciuta come bolla"
PROMPT;

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => self::API_VERSION,
                'content-type' => 'application/json',
            ])->timeout(60)->post(self::API_URL, [
                'model' => env('ANTHROPIC_MODEL', self::DEFAULT_MODEL),
                'max_tokens' => self::DEFAULT_MAX_TOKENS,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $mediaType,
                                'data' => $imageData,
                            ],
                        ],
                        ['type' => 'text', 'text' => $prompt],
                    ],
                ]],
            ]);

            if (!$response->successful()) {
                Log::error('Claude Vision API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Claude API non raggiungibile: ' . $response->status());
            }

            $data = $response->json();
            $testoRisposta = $data['content'][0]['text'] ?? '';

            // Rimuovi eventuali markdown ``` o ```json
            $jsonText = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($testoRisposta));

            $dati = json_decode($jsonText, true);
            if (!is_array($dati)) {
                Log::warning('Claude Vision: risposta non JSON', ['raw' => $testoRisposta]);
                throw new \Exception('Risposta AI non è JSON valido');
            }

            // Normalizza campi per compatibilità con flusso esistente
            return [
                'fornitore' => $dati['fornitore'] ?? '',
                'numero_bolla' => $dati['numero_bolla'] ?? '',
                'data_bolla' => $dati['data_bolla'] ?? '',
                'categoria' => $dati['categoria'] ?? '',
                'descrizione' => $dati['descrizione_carta'] ?? '',
                'grammatura' => $dati['grammatura'] ?? null,
                'formato' => $dati['formato'] ?? '',
                'quantita' => $dati['quantita'] ?? null,
                'unita_misura' => $dati['unita_misura'] ?? 'fg',
                'lotto' => $dati['lotto'] ?? '',
                'note' => $dati['note'] ?? '',
                'ocr_raw' => $testoRisposta,
                'tokens_input' => $data['usage']['input_tokens'] ?? null,
                'tokens_output' => $data['usage']['output_tokens'] ?? null,
                'costo_stimato_eur' => self::calcolaCosto($data['usage'] ?? []),
            ];
        } catch (\Exception $e) {
            Log::error('Claude Vision exception', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Calcola costo stimato in EUR per una singola chiamata (tariffe Haiku 4.5).
     */
    private static function calcolaCosto(array $usage): float
    {
        $in = $usage['input_tokens'] ?? 0;
        $out = $usage['output_tokens'] ?? 0;
        $costoUsd = ($in * 0.80 + $out * 4.00) / 1_000_000;
        return round($costoUsd * 0.92, 5); // USD→EUR approx
    }
}
