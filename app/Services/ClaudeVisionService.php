<?php

namespace App\Services;

use App\Services\Api\ClaudeApiClient;
use Illuminate\Support\Facades\Log;

/**
 * Integrazione API Anthropic Claude (Vision).
 * Analizza foto bolle fornitore e restituisce dati strutturati.
 * HTTP delegato a ClaudeApiClient (DRY).
 */
class ClaudeVisionService
{
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
        $client = new ClaudeApiClient();
        if (!$client->isConfigured()) {
            throw new \Exception('ANTHROPIC_API_KEY non configurata');
        }

        if (!file_exists($imagePath)) {
            throw new \Exception("File immagine non trovato: {$imagePath}");
        }

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

        $result = $client->sendVisionMessage($imagePath, $prompt, self::DEFAULT_MAX_TOKENS, 60);

        if (!$result['ok']) {
            throw new \Exception('Claude API non raggiungibile: ' . ($result['error'] ?? 'errore'));
        }

        $testoRisposta = $result['text'] ?? '';

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
            'tokens_input' => $result['usage']['input_tokens'] ?? null,
            'tokens_output' => $result['usage']['output_tokens'] ?? null,
            'costo_stimato_eur' => self::calcolaCosto($result['usage'] ?? []),
        ];
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
