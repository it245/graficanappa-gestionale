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
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => self::API_VERSION,
                'content-type' => 'application/json',
            ])
                ->timeout(self::TIMEOUT_SECONDS)
                ->retry(self::MAX_RETRIES, 1000, function ($exception) {
                    // Retry solo su errori di rete/timeout, non 4xx
                    return $exception instanceof \Illuminate\Http\Client\ConnectionException;
                })
                ->post(self::ENDPOINT, $payload);

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
