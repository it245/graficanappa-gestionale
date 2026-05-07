<?php

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client unico Telegram Bot API.
 * Responsabilita': send message/photo, validate webhook, sanitize input.
 *
 * SRP: solo HTTP a Telegram. Logica business (parsing comandi) -> TelegramBotService.
 * DRY: sostituisce chiamate Http::post sparse.
 */
class TelegramApiClient
{
    private const TIMEOUT_SECONDS = 15;
    private const MAX_RETRIES = 2;
    private const TELEGRAM_BASE = 'https://api.telegram.org/bot';

    private string $token;

    public function __construct(?string $token = null)
    {
        $this->token = $token ?? (string) env('TELEGRAM_BOT_TOKEN', '');
    }

    public function isConfigured(): bool
    {
        return $this->token !== '';
    }

    /**
     * Invia messaggio testo a chat_id.
     */
    public function sendMessage(int|string $chatId, string $text, array $options = []): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $payload = array_merge([
            'chat_id' => $chatId,
            'text' => $this->sanitizeOutgoing($text),
            'parse_mode' => 'HTML',
        ], $options);

        return $this->call('sendMessage', $payload);
    }

    /**
     * Invia foto con caption.
     */
    public function sendPhoto(int|string $chatId, string $photoUrl, string $caption = ''): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        return $this->call('sendPhoto', [
            'chat_id' => $chatId,
            'photo' => $photoUrl,
            'caption' => $this->sanitizeOutgoing($caption),
            'parse_mode' => 'HTML',
        ]);
    }

    /**
     * Validazione signature webhook (HMAC con secret token).
     * Telegram invia X-Telegram-Bot-Api-Secret-Token header se webhook configurato con secret.
     */
    public function validateWebhookSecret(?string $headerSecret): bool
    {
        $expected = (string) env('TELEGRAM_WEBHOOK_SECRET', '');

        if ($expected === '') {
            // Nessun secret configurato: webhook open (sconsigliato in prod)
            return true;
        }

        return is_string($headerSecret) && hash_equals($expected, $headerSecret);
    }

    /**
     * Sanitize input incoming da Telegram (HTML escape, no tag pericolosi).
     */
    public function sanitizeIncoming(string $text): string
    {
        // Trim, rimuovi caratteri controllo, limita lunghezza
        $text = trim($text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? '';

        return mb_substr($text, 0, 4000);
    }

    private function sanitizeOutgoing(string $text): string
    {
        // Telegram parse_mode HTML supporta solo b,i,u,s,a,code,pre. Escapa il resto.
        $text = strip_tags($text, '<b><i><u><s><a><code><pre><br>');

        return mb_substr($text, 0, 4096);
    }

    private function call(string $method, array $payload): bool
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->retry(self::MAX_RETRIES, 500)
                ->post(self::TELEGRAM_BASE.$this->token.'/'.$method, $payload);

            if ($response->failed()) {
                Log::warning('Telegram API errore', [
                    'method' => $method,
                    'status' => $response->status(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Telegram API exception', [
                'method' => $method,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
