<?php

declare(strict_types=1);

namespace App\Modules\Notifiche\Senders;

use App\Models\Operatore;
use App\Modules\Notifiche\Contracts\NotificaSenderInterface;
use App\Modules\Notifiche\Enums\CanaleNotifica;
use App\Modules\Notifiche\ValueObjects\Notifica;
use App\Services\Api\TelegramApiClient;
use Illuminate\Support\Facades\Log;

/**
 * Sender Telegram: usa TelegramApiClient (wrapper HTTP unico).
 *
 * Destinatario accettato:
 *  - int|string  -> chat_id
 *  - Operatore   -> tenta $operatore->telegram_chat_id (colonna opzionale)
 *
 * NON usa TelegramBotService (legacy) per non duplicare logica business.
 *
 * Esempio:
 *   $sender = new TelegramSender(app(TelegramApiClient::class));
 *   $sender->send(new Notifica('Test', 'Ciao', CanaleNotifica::Telegram,
 *       PrioritaNotifica::Normale, destinatario: 123456789));
 */
final class TelegramSender implements NotificaSenderInterface
{
    public function __construct(
        private readonly TelegramApiClient $client,
    ) {
    }

    public function canale(): CanaleNotifica
    {
        return CanaleNotifica::Telegram;
    }

    public function isAvailable(): bool
    {
        return $this->client->isConfigured();
    }

    public function send(Notifica $n): bool
    {
        $chatId = $this->resolveChatId($n->destinatario);

        if ($chatId === null) {
            Log::warning('TelegramSender: chat_id non risolvibile', [
                'destinatario_type' => get_debug_type($n->destinatario),
                'titolo' => $n->titolo,
            ]);

            return false;
        }

        $text = $this->formatta($n);

        return $this->client->sendMessage($chatId, $text);
    }

    private function resolveChatId(mixed $destinatario): int|string|null
    {
        if (is_int($destinatario) || is_string($destinatario)) {
            return $destinatario;
        }

        if ($destinatario instanceof Operatore) {
            $chat = $destinatario->telegram_chat_id ?? null;

            return is_int($chat) || is_string($chat) ? $chat : null;
        }

        return null;
    }

    private function formatta(Notifica $n): string
    {
        $prefix = match ($n->priorita->value) {
            'critica' => '<b>[CRITICA]</b> ',
            'alta' => '<b>[ALTA]</b> ',
            default => '',
        };

        return $prefix.'<b>'.$n->titolo."</b>\n".$n->messaggio;
    }
}
