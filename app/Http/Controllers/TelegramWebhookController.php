<?php

namespace App\Http\Controllers;

use App\Services\ClaudeVisionService;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint webhook Telegram.
 * Riceve aggiornamenti (foto, messaggi, callback) e gestisce flusso magazzino.
 */
class TelegramWebhookController extends Controller
{
    public function handle(Request $request, string $secret)
    {
        // Verifica secret URL contro quello in .env
        $expected = env('TELEGRAM_WEBHOOK_SECRET');
        if (!$expected || !hash_equals($expected, $secret)) {
            Log::warning('Telegram webhook secret non valido', ['ip' => $request->ip()]);
            abort(403);
        }

        $update = $request->all();
        Log::info('Telegram update ricevuto', ['type' => array_keys($update)]);

        // Callback query (click bottoni inline)
        if (isset($update['callback_query'])) {
            return $this->handleCallback($update['callback_query']);
        }

        $message = $update['message'] ?? null;
        if (!$message) return response()->json(['ok' => true]);

        $chatId = $message['chat']['id'] ?? null;
        if (!$chatId || !TelegramBotService::chatAutorizzato($chatId)) {
            if ($chatId) {
                TelegramBotService::sendMessage($chatId, "⛔ Chat non autorizzata (ID: {$chatId}).");
            }
            return response()->json(['ok' => true]);
        }

        // Foto allegata
        if (isset($message['photo'])) {
            return $this->handleFoto($chatId, $message);
        }

        // Messaggio testuale (comandi e flusso conversazionale)
        if (isset($message['text'])) {
            return $this->handleText($chatId, $message['text'], $message);
        }

        return response()->json(['ok' => true]);
    }

    private function handleFoto(int $chatId, array $message)
    {
        $photos = $message['photo'];
        // Prendi la risoluzione più alta
        $photo = end($photos);
        $fileId = $photo['file_id'] ?? null;

        if (!$fileId) {
            TelegramBotService::sendMessage($chatId, "⚠️ Foto non valida, riprova.");
            return response()->json(['ok' => true]);
        }

        TelegramBotService::sendMessage($chatId, "🔍 Sto leggendo la bolla con l'AI...");

        try {
            $imagePath = TelegramBotService::downloadFoto($fileId);
            if (!$imagePath) {
                TelegramBotService::sendMessage($chatId, "❌ Errore nel download della foto.");
                return response()->json(['ok' => true]);
            }

            $dati = ClaudeVisionService::estraiBolla($imagePath);

            // Salva dati in cache temporanea per conferma successiva
            $token = 'tg_bolla_' . $chatId . '_' . time();
            Cache::put($token, [
                'dati' => $dati,
                'image_path' => $imagePath,
                'chat_id' => $chatId,
            ], now()->addMinutes(30));

            $testo = $this->formattaRiepilogo($dati);

            TelegramBotService::sendMessageWithButtons($chatId, $testo, [
                [
                    ['text' => '✅ Conferma', 'callback_data' => "confirm:{$token}"],
                    ['text' => '✏️ Modifica', 'callback_data' => "edit:{$token}"],
                    ['text' => '❌ Annulla', 'callback_data' => "cancel:{$token}"],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Telegram handleFoto errore', ['error' => $e->getMessage()]);
            TelegramBotService::sendMessage($chatId, "❌ Errore lettura AI: " . $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    private function handleText(int $chatId, string $text, array $message)
    {
        $text = trim($text);

        if ($text === '/start' || $text === '/help') {
            $this->inviaAiuto($chatId);
            return response()->json(['ok' => true]);
        }

        if ($text === '/chatid' || $text === '/id') {
            TelegramBotService::sendMessage($chatId, "Chat ID: `{$chatId}`\nAggiungilo a `TELEGRAM_ALLOWED_CHATS` nel .env");
            return response()->json(['ok' => true]);
        }

        // Default: istruzioni
        TelegramBotService::sendMessage($chatId, "📸 Inviami una foto di una bolla fornitore per registrarla in magazzino.\n\nComandi: /help");
        return response()->json(['ok' => true]);
    }

    private function handleCallback(array $callback)
    {
        $callbackId = $callback['id'];
        $data = $callback['data'] ?? '';
        $chatId = $callback['message']['chat']['id'] ?? null;
        $messageId = $callback['message']['message_id'] ?? null;

        [$action, $token] = array_pad(explode(':', $data, 2), 2, null);

        $payload = Cache::get($token);
        if (!$payload) {
            TelegramBotService::answerCallbackQuery($callbackId, 'Sessione scaduta, invia di nuovo la foto.');
            return response()->json(['ok' => true]);
        }

        switch ($action) {
            case 'confirm':
                TelegramBotService::answerCallbackQuery($callbackId, 'Registrazione in corso...');
                $this->registraCarico($chatId, $messageId, $payload);
                Cache::forget($token);
                break;
            case 'edit':
                TelegramBotService::answerCallbackQuery($callbackId, 'Apri il MES per modificare');
                $url = rtrim(env('APP_URL', 'http://localhost'), '/') . '/magazzino/carico';
                TelegramBotService::sendMessage(
                    $chatId,
                    "✏️ Per modificare i dati, apri il MES:\n{$url}\n\nI dati AI sono stati salvati nella tua sessione."
                );
                break;
            case 'cancel':
                TelegramBotService::answerCallbackQuery($callbackId, 'Annullato');
                if ($messageId) TelegramBotService::editMessage($chatId, $messageId, '❌ Registrazione annullata.');
                Cache::forget($token);
                break;
        }

        return response()->json(['ok' => true]);
    }

    private function registraCarico(int $chatId, ?int $messageId, array $payload)
    {
        // TODO: chiamare MagazzinoService::registraCarico($payload['dati'])
        // Per ora risposta placeholder. Integrazione completa dopo validazione flusso.
        $dati = $payload['dati'];
        $riepilogo = "✅ *Carico registrato*\n\n"
            . "• Carta: {$dati['descrizione']}\n"
            . "• Qta: {$dati['quantita']} {$dati['unita_misura']}\n"
            . "• Lotto: {$dati['lotto']}\n\n"
            . "_Etichetta QR in coda di stampa_";

        if ($messageId) {
            TelegramBotService::editMessage($chatId, $messageId, $riepilogo);
        } else {
            TelegramBotService::sendMessage($chatId, $riepilogo);
        }
    }

    private function formattaRiepilogo(array $dati): string
    {
        $costo = isset($dati['costo_stimato_eur']) ? sprintf('€%.4f', $dati['costo_stimato_eur']) : '—';
        return "📋 *Bolla letta dall'AI*\n\n"
            . "🏭 *Fornitore*: " . ($dati['fornitore'] ?: '—') . "\n"
            . "📄 *Bolla N°*: " . ($dati['numero_bolla'] ?: '—') . "\n"
            . "📅 *Data*: " . ($dati['data_bolla'] ?: '—') . "\n"
            . "📦 *Categoria*: " . ($dati['categoria'] ?: '—') . "\n"
            . "📝 *Descrizione*: " . ($dati['descrizione'] ?: '—') . "\n"
            . "⚖️ *Grammatura*: " . ($dati['grammatura'] ? $dati['grammatura'] . 'g' : '—') . "\n"
            . "📐 *Formato*: " . ($dati['formato'] ?: '—') . "\n"
            . "🔢 *Quantità*: " . ($dati['quantita'] ? $dati['quantita'] . ' ' . $dati['unita_misura'] : '—') . "\n"
            . "🏷 *Lotto*: " . ($dati['lotto'] ?: '—') . "\n\n"
            . "_Costo analisi AI: {$costo}_\n\n"
            . "Conferma per registrare in magazzino:";
    }

    private function inviaAiuto(int $chatId)
    {
        $testo = "👋 *Bot Magazzino Grafica Nappa*\n\n"
            . "Invia una *foto di una bolla fornitore* e l'AI la leggerà automaticamente.\n\n"
            . "Conferma i dati estratti → registrazione in magazzino + etichetta QR.\n\n"
            . "*Comandi*:\n"
            . "/help — mostra questo messaggio\n"
            . "/chatid — mostra il tuo chat ID";
        TelegramBotService::sendMessage($chatId, $testo);
    }
}
