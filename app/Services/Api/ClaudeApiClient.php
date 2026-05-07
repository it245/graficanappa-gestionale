<?php

namespace App\Services\Api;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client unico per chiamate Anthropic Claude API.
 * Responsabilita': retry, timeout, rate-limit, log sanitized, validation response.
 *
 * SRP: solo HTTP a Claude. Contesto/prompt building -> AiService.
 * DRY: sostituisce 4+ chiamate Http::post sparse.
 */
class ClaudeApiClient
{
    private const ENDPOINT = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';
    private const DEFAULT_MODEL = 'claude-haiku-4-5-20251001';
    private const TIMEOUT_SECONDS = 30;
    private const MAX_RETRIES = 2;

    private string $apiKey;
    private string $model;

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = $apiKey ?? (string) env('ANTHROPIC_API_KEY', '');
        $this->model = $model ?? (string) env('ANTHROPIC_MODEL', self::DEFAULT_MODEL);
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Invia messaggi a Claude e ritorna risposta testuale.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function sendMessages(array $messages, string $systemPrompt = '', int $maxTokens = 1024): string
    {
        if (! $this->isConfigured()) {
            return '[Claude API non configurata]';
        }

        $payload = [
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'messages' => $messages,
        ];

        if ($systemPrompt !== '') {
            $payload['system'] = $systemPrompt;
        }

        try {
            $response = $this->httpClient($maxTokens)->post(self::ENDPOINT, $payload);

            if ($response->failed()) {
                Log::warning('Claude API errore', [
                    'status' => $response->status(),
                    'body' => mb_substr((string) $response->body(), 0, 500),
                ]);

                return '[Errore Claude API: '.$response->status().']';
            }

            return $this->extractText($response->json());
        } catch (RequestException $e) {
            Log::error('Claude API exception', ['message' => $e->getMessage()]);

            return '[Errore comunicazione Claude]';
        } catch (\Throwable $e) {
            Log::error('Claude API throwable', ['message' => $e->getMessage()]);

            return '[Errore interno]';
        }
    }

    /**
     * Invia richiesta vision (immagine + prompt testuale) e ritorna response raw.
     * Differenza vs sendMessages: ritorna array completo (per usage/model/raw text)
     * e i chiamanti gestiscono parsing JSON specifico (DDT, bolle, ecc.).
     *
     * @return array{ok: bool, text?: string, usage?: array, model?: string, error?: string, status?: int}
     */
    public function sendVisionMessage(
        string $imagePath,
        string $prompt,
        int $maxTokens = 1500,
        int $timeoutSeconds = 60
    ): array {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'API key non configurata'];
        }

        if (! file_exists($imagePath)) {
            return ['ok' => false, 'error' => "File non trovato: {$imagePath}"];
        }

        $mediaType = $this->detectMediaType($imagePath);
        $imageData = base64_encode((string) file_get_contents($imagePath));

        $payload = [
            'model' => $this->model,
            'max_tokens' => $maxTokens,
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
        ];

        try {
            $response = $this->httpClient($maxTokens, $timeoutSeconds, true)
                ->post(self::ENDPOINT, $payload);

            if (! $response->successful()) {
                Log::error('Claude Vision API errore', [
                    'status' => $response->status(),
                    'body' => mb_substr((string) $response->body(), 0, 500),
                ]);

                return [
                    'ok' => false,
                    'status' => $response->status(),
                    'error' => 'API error: '.$response->status(),
                ];
            }

            $body = $response->json() ?? [];

            return [
                'ok' => true,
                'text' => $body['content'][0]['text'] ?? '',
                'usage' => $body['usage'] ?? null,
                'model' => $body['model'] ?? $this->model,
            ];
        } catch (\Throwable $e) {
            Log::error('Claude Vision exception', ['message' => $e->getMessage()]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function httpClient(int $maxTokens, ?int $timeoutSeconds = null, bool $skipVerify = false): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => self::API_VERSION,
            'content-type' => 'application/json',
        ])
            ->timeout($timeoutSeconds ?? self::TIMEOUT_SECONDS)
            ->retry(self::MAX_RETRIES, 1000, function ($exception) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException;
            });

        if ($skipVerify) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    private function detectMediaType(string $path): string
    {
        $detected = function_exists('mime_content_type') ? @mime_content_type($path) : false;
        if ($detected && in_array($detected, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            return $detected;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    /**
     * Estrae il testo dalla risposta Claude (formato content-blocks).
     */
    private function extractText(?array $json): string
    {
        if (! is_array($json) || ! isset($json['content'])) {
            return '[Risposta Claude non valida]';
        }

        $text = '';
        foreach ($json['content'] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $text .= $block['text'] ?? '';
            }
        }

        return $text !== '' ? $text : '[Risposta Claude vuota]';
    }
}
