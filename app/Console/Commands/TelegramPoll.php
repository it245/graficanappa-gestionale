<?php

namespace App\Console\Commands;

use App\Http\Controllers\TelegramWebhookController;
use App\Services\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Long-polling Telegram bot.
 *
 * Alternativa al webhook: non serve tunnel/HTTPS pubblico.
 * Il bot chiede a Telegram ogni 30s se ci sono nuovi update.
 *
 * Uso:
 *   php artisan telegram:poll              → avvia loop infinito
 *   php artisan telegram:poll --once       → singola iterazione (utile per test)
 *
 * Produzione: avviare come servizio Windows via NSSM sul server .60.
 */
class TelegramPoll extends Command
{
    protected $signature = 'telegram:poll {--once} {--timeout=30}';
    protected $description = 'Avvia il bot Telegram in modalità long-polling (no tunnel pubblico richiesto)';

    /** Chiave Cache dove salvare l\'offset ultimo update processato */
    private const OFFSET_KEY = 'telegram_bot_last_offset';

    public function handle(TelegramWebhookController $controller): int
    {
        if (!env('TELEGRAM_BOT_TOKEN')) {
            $this->error('TELEGRAM_BOT_TOKEN mancante nel .env');
            return 1;
        }

        // Rimuovi eventuale webhook attivo (non compatibile con polling)
        $info = TelegramBotService::getWebhookInfo();
        if (!empty($info['result']['url'])) {
            $this->warn('Webhook attivo: ' . $info['result']['url']);
            $this->info('Rimozione webhook per abilitare polling...');
            TelegramBotService::deleteWebhook();
        }

        $timeout = (int) $this->option('timeout');
        $once = $this->option('once');

        $this->info("Bot avviato in polling. Timeout={$timeout}s. Ctrl+C per uscire.");
        Log::info('Telegram polling avviato', ['timeout' => $timeout, 'once' => $once]);

        do {
            $offset = Cache::get(self::OFFSET_KEY);
            try {
                $response = TelegramBotService::getUpdates($offset, $timeout);
            } catch (\Exception $e) {
                Log::error('Telegram polling errore', ['error' => $e->getMessage()]);
                $this->error('Errore: ' . $e->getMessage());
                sleep(5);
                continue;
            }

            if (!$response || !($response['ok'] ?? false)) {
                $this->warn('Risposta non valida da Telegram, retry in 5s');
                sleep(5);
                continue;
            }

            $updates = $response['result'] ?? [];
            if (empty($updates)) {
                if ($once) break;
                continue;
            }

            foreach ($updates as $update) {
                $updateId = $update['update_id'] ?? null;
                try {
                    $controller->processUpdate($update);
                } catch (\Throwable $e) {
                    Log::error('Errore processUpdate', [
                        'update_id' => $updateId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $this->error("Errore update #{$updateId}: " . $e->getMessage());
                }

                if ($updateId !== null) {
                    Cache::forever(self::OFFSET_KEY, $updateId + 1);
                }
            }

            $this->line('Processati ' . count($updates) . ' update (ultimo id: ' . end($updates)['update_id'] . ')');

            if ($once) break;
        } while (true);

        return 0;
    }
}
