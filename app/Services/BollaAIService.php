<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Analisi bolle di carico via API Claude (Anthropic).
 * Usa Claude Haiku 4.5 con vision per OCR strutturato DDT italiano.
 */
class BollaAIService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const ANTHROPIC_VERSION = '2023-06-01';

    public static function analizzaBolla(string $imagePath): array
    {
        $apiKey = env('ANTHROPIC_API_KEY');
        if (!$apiKey) {
            Log::error('ANTHROPIC_API_KEY non configurata');
            return ['ok' => false, 'error' => 'API key mancante'];
        }

        if (!file_exists($imagePath)) {
            return ['ok' => false, 'error' => 'Foto non trovata'];
        }

        $imageData = base64_encode(file_get_contents($imagePath));
        $mediaType = self::mediaTypeFromPath($imagePath);
        $model = env('ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001');

        $prompt = <<<'PROMPT'
Analizza questa bolla di carico italiana (DDT - Documento di Trasporto) ed estrai i dati strutturati.

Rispondi SOLO con un JSON valido, senza testo prima o dopo, in questo formato esatto:

{
  "fornitore": "Nome del fornitore",
  "numero_ddt": "numero documento",
  "data": "YYYY-MM-DD",
  "destinatario": "Nome destinatario",
  "righe": [
    {
      "codice": "codice articolo o null",
      "descrizione": "descrizione articolo",
      "qta": numero,
      "um": "KG o PZ o MT o FG",
      "peso_kg": numero o null
    }
  ],
  "peso_totale_kg": numero o null,
  "numero_colli": numero o null,
  "note": "eventuali note o null"
}

Se un campo non è leggibile o assente, usa null. Se la foto non è una bolla, ritorna {"ok": false, "motivo": "spiegazione"}.
Priorità: dati carta (formato, grammatura, tipo). Non inventare valori. Sii preciso sui numeri.
PROMPT;

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => self::ANTHROPIC_VERSION,
                    'content-type' => 'application/json',
                ])
                ->timeout(60)
                ->post(self::API_URL, [
                    'model' => $model,
                    'max_tokens' => 1500,
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
                            [
                                'type' => 'text',
                                'text' => $prompt,
                            ],
                        ],
                    ]],
                ]);

            if (!$response->successful()) {
                Log::error('Claude API error', ['status' => $response->status(), 'body' => $response->body()]);
                return ['ok' => false, 'error' => 'API error: ' . $response->status()];
            }

            $body = $response->json();
            $text = $body['content'][0]['text'] ?? '';

            if (empty($text)) {
                return ['ok' => false, 'error' => 'Risposta vuota da Claude'];
            }

            $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($text));
            $parsed = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Claude response non JSON', ['raw' => $text]);
                return ['ok' => false, 'error' => 'JSON invalido', 'raw' => $text];
            }

            if (isset($parsed['ok']) && $parsed['ok'] === false) {
                return $parsed;
            }

            $parsed['ok'] = true;
            $parsed['_usage'] = $body['usage'] ?? null;
            $parsed['_model'] = $body['model'] ?? $model;

            return $parsed;
        } catch (\Exception $e) {
            Log::error('BollaAIService exception', ['error' => $e->getMessage()]);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private static function mediaTypeFromPath(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    public static function formatPerTelegram(array $data): string
    {
        if (!($data['ok'] ?? false)) {
            return "❌ Non sono riuscito a leggere la bolla.\n" . ($data['error'] ?? '');
        }

        $out = "📄 *Bolla analizzata*\n\n";
        $out .= "*Fornitore:* " . ($data['fornitore'] ?? '—') . "\n";
        $out .= "*DDT:* " . ($data['numero_ddt'] ?? '—') . "\n";
        $out .= "*Data:* " . ($data['data'] ?? '—') . "\n";
        if (!empty($data['destinatario'])) {
            $out .= "*Destinatario:* " . $data['destinatario'] . "\n";
        }
        $out .= "\n*Righe:*\n";
        foreach (($data['righe'] ?? []) as $r) {
            $qta = $r['qta'] ?? '?';
            $um = $r['um'] ?? '';
            $desc = $r['descrizione'] ?? '—';
            $cod = !empty($r['codice']) ? "`{$r['codice']}` " : '';
            $peso = !empty($r['peso_kg']) ? " ({$r['peso_kg']} kg)" : '';
            $out .= "• {$cod}{$desc} — {$qta} {$um}{$peso}\n";
        }
        if (!empty($data['peso_totale_kg'])) {
            $out .= "\n*Peso totale:* {$data['peso_totale_kg']} kg";
        }
        if (!empty($data['numero_colli'])) {
            $out .= "\n*Colli:* {$data['numero_colli']}";
        }
        return $out;
    }
}
