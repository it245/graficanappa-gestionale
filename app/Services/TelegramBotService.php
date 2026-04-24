<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Integrazione Telegram Bot API.
 * Gestisce invio messaggi, download foto, tastiere inline.
 */
class TelegramBotService
{
    private const API_BASE = 'https://api.telegram.org/bot';
    private const FILE_BASE = 'https://api.telegram.org/file/bot';

    /** Whitelist chat_id autorizzati (da .env TELEGRAM_ALLOWED_CHATS="123,456") */
    public static function chatAutorizzato(int $chatId): bool
    {
        $allowed = array_filter(array_map('trim', explode(',', env('TELEGRAM_ALLOWED_CHATS', ''))));
        if (empty($allowed)) return true; // nessun filtro = tutti OK (da configurare in prod)
        return in_array((string) $chatId, $allowed, true);
    }

    /** Invia messaggio testuale */
    public static function sendMessage(int $chatId, string $text, array $options = []): ?array
    {
        return self::call('sendMessage', array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ], $options));
    }

    /** Invia messaggio con tastiera inline (bottoni callback) */
    public static function sendMessageWithButtons(int $chatId, string $text, array $buttons): ?array
    {
        return self::sendMessage($chatId, $text, [
            'reply_markup' => json_encode([
                'inline_keyboard' => $buttons,
            ]),
        ]);
    }

    /** Risponde a una callback query (fa sparire "loading" del bottone) */
    public static function answerCallbackQuery(string $callbackId, ?string $text = null): ?array
    {
        return self::call('answerCallbackQuery', array_filter([
            'callback_query_id' => $callbackId,
            'text' => $text,
        ]));
    }

    /** Modifica un messaggio esistente (es. togliere bottoni dopo click) */
    public static function editMessage(int $chatId, int $messageId, string $newText): ?array
    {
        return self::call('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $newText,
            'parse_mode' => 'Markdown',
        ]);
    }

    /**
     * Scarica foto da Telegram e salva localmente.
     * @return string|null percorso assoluto file salvato o null se errore
     */
    public static function downloadFoto(string $fileId): ?string
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        if (!$token) {
            Log::error('TELEGRAM_BOT_TOKEN non configurato');
            return null;
        }

        // Step 1: ottieni file_path da Telegram
        $resp = self::call('getFile', ['file_id' => $fileId]);
        $filePath = $resp['result']['file_path'] ?? null;
        if (!$filePath) {
            Log::error('Telegram getFile fallito', ['file_id' => $fileId]);
            return null;
        }

        // Step 2: scarica contenuto file
        $fileUrl = self::FILE_BASE . $token . '/' . $filePath;
        $contents = @file_get_contents($fileUrl);
        if ($contents === false) {
            Log::error('Telegram download fallito', ['url' => $fileUrl]);
            return null;
        }

        // Step 3: salva in storage privato
        $ext = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'jpg';
        $nomeLocale = 'telegram_' . date('Ymd_His') . '_' . substr(md5($fileId), 0, 8) . '.' . $ext;
        $relativePath = 'bolle/' . $nomeLocale;
        Storage::disk('local')->put($relativePath, $contents);

        return storage_path('app/private/' . $relativePath);
    }

    /**
     * Long-polling: chiede a Telegram i nuovi update.
     * @param int|null $offset id ultimo update processato + 1
     * @param int $timeout secondi di attesa lato server Telegram (max 50)
     */
    public static function getUpdates(?int $offset = null, int $timeout = 30): ?array
    {
        $params = ['timeout' => $timeout];
        if ($offset !== null) $params['offset'] = $offset;
        return self::call('getUpdates', $params, $timeout + 10);
    }

    /** Registra webhook URL su Telegram (eseguito manualmente al setup) */
    public static function setWebhook(string $url): ?array
    {
        return self::call('setWebhook', ['url' => $url]);
    }

    /** Rimuove webhook (per debug o switch modalità polling) */
    public static function deleteWebhook(): ?array
    {
        return self::call('deleteWebhook');
    }

    /** Info webhook attivo */
    public static function getWebhookInfo(): ?array
    {
        return self::call('getWebhookInfo');
    }

    /** Chiamata HTTP generica Telegram API */
    private static function call(string $method, array $params = [], int $timeout = 15): ?array
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        if (!$token) {
            Log::error('TELEGRAM_BOT_TOKEN non configurato');
            return null;
        }

        try {
            $r = Http::withoutVerifying()->timeout($timeout)->post(self::API_BASE . $token . '/' . $method, $params);
            return $r->json();
        } catch (\Exception $e) {
            Log::error('Telegram API exception', ['method' => $method, 'error' => $e->getMessage()]);
            return null;
        }
    }
}
