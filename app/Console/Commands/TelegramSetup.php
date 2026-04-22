<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

/**
 * Setup e diagnostica webhook Telegram.
 *
 * Uso:
 *   php artisan telegram:setup            → registra webhook su APP_URL
 *   php artisan telegram:setup --info     → mostra info webhook attivo
 *   php artisan telegram:setup --delete   → rimuove webhook
 */
class TelegramSetup extends Command
{
    protected $signature = 'telegram:setup {--info} {--delete} {--url=}';
    protected $description = 'Configura webhook Telegram per bot magazzino';

    public function handle(): int
    {
        if ($this->option('info')) {
            $info = TelegramBotService::getWebhookInfo();
            $this->line(json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return 0;
        }

        if ($this->option('delete')) {
            $r = TelegramBotService::deleteWebhook();
            $this->info('Webhook rimosso: ' . json_encode($r));
            return 0;
        }

        $secret = env('TELEGRAM_WEBHOOK_SECRET');
        if (!$secret || strlen($secret) < 16) {
            $this->error('TELEGRAM_WEBHOOK_SECRET mancante o troppo corto (min 16 caratteri).');
            return 1;
        }

        $baseUrl = $this->option('url') ?: env('APP_URL');
        if (!$baseUrl) {
            $this->error('APP_URL non configurato, usa --url=https://...');
            return 1;
        }

        $webhookUrl = rtrim($baseUrl, '/') . '/telegram/webhook/' . $secret;
        $this->info("Registrazione webhook: {$webhookUrl}");

        $r = TelegramBotService::setWebhook($webhookUrl);
        $this->line(json_encode($r, JSON_PRETTY_PRINT));

        if (($r['ok'] ?? false) === true) {
            $this->info('Webhook registrato correttamente.');
            return 0;
        }

        $this->error('Registrazione fallita.');
        return 1;
    }
}
