<?php

namespace App\Http\Controllers;

use App\Models\MagazzinoArticolo;
use App\Models\MagazzinoMovimento;
use App\Services\MagazzinoService;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint webhook Telegram.
 * Base bot magazzino: riceve comandi e foto, salva in storage.
 * L'analisi AI (Claude Vision) sarà integrata in seconda fase.
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
        Log::info('Telegram update ricevuto', ['keys' => array_keys($update)]);

        // Callback query (click bottoni inline)
        if (isset($update['callback_query'])) {
            return $this->handleCallback($update['callback_query']);
        }

        $message = $update['message'] ?? null;
        if (!$message) {
            return response()->json(['ok' => true]);
        }

        $chatId = $message['chat']['id'] ?? null;
        if (!$chatId) {
            return response()->json(['ok' => true]);
        }

        // Se chat non autorizzata, risponde comunque con il chat_id (utile per setup iniziale)
        if (!TelegramBotService::chatAutorizzato($chatId)) {
            TelegramBotService::sendMessage(
                $chatId,
                "⛔ Chat non autorizzata.\n\n"
                . "Per abilitare questa chat:\n"
                . "1. Copia il Chat ID qui sotto\n"
                . "2. Aggiungilo a `TELEGRAM_ALLOWED_CHATS` nel .env del MES\n"
                . "3. Riavvia e riprova\n\n"
                . "Chat ID: `{$chatId}`"
            );
            return response()->json(['ok' => true]);
        }

        // Foto allegata
        if (isset($message['photo'])) {
            return $this->handleFoto($chatId, $message);
        }

        // Messaggio testuale
        if (isset($message['text'])) {
            return $this->handleText($chatId, $message['text']);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Gestisce foto ricevuta.
     * Per ora: scarica, salva in storage, conferma ricezione.
     * Integrazione AI analisi foto in fase successiva.
     */
    private function handleFoto(int $chatId, array $message)
    {
        $photos = $message['photo'];
        $photo = end($photos); // risoluzione massima
        $fileId = $photo['file_id'] ?? null;

        if (!$fileId) {
            TelegramBotService::sendMessage($chatId, "⚠️ Foto non valida. Riprova.");
            return response()->json(['ok' => true]);
        }

        $imagePath = TelegramBotService::downloadFoto($fileId);
        if (!$imagePath) {
            TelegramBotService::sendMessage($chatId, "❌ Errore nel download della foto da Telegram.");
            return response()->json(['ok' => true]);
        }

        $filename = basename($imagePath);
        Log::info('Telegram foto salvata', ['chat_id' => $chatId, 'file' => $filename]);

        $testo = "📸 *Foto ricevuta e salvata*\n\n"
            . "File: `{$filename}`\n"
            . "Dimensione: " . round(filesize($imagePath) / 1024, 1) . " KB\n\n"
            . "_L'analisi automatica delle bolle sarà attivata prossimamente._\n"
            . "_Per ora la foto è archiviata nel MES e potrà essere rielaborata quando l'AI sarà attiva._";

        TelegramBotService::sendMessage($chatId, $testo);

        return response()->json(['ok' => true]);
    }

    private function handleText(int $chatId, string $text)
    {
        $text = trim($text);

        // Parsing comandi con argomenti (es. "/giacenza GC1")
        [$cmd, $args] = array_pad(explode(' ', $text, 2), 2, '');
        $cmd = strtolower(trim($cmd));
        $args = trim($args);

        switch ($cmd) {
            case '/start':
            case '/help':
                $this->inviaAiuto($chatId);
                break;

            case '/id':
            case '/chatid':
                TelegramBotService::sendMessage(
                    $chatId,
                    "🆔 *Chat ID*: `{$chatId}`\n\n"
                    . "Aggiungilo a `TELEGRAM_ALLOWED_CHATS` nel `.env` del MES per autorizzare questa chat."
                );
                break;

            case '/ping':
                TelegramBotService::sendMessage($chatId, "🏓 pong — bot attivo e autorizzato.");
                break;

            case '/status':
                $this->inviaStatus($chatId);
                break;

            case '/giacenza':
            case '/cerca':
                $this->inviaGiacenza($chatId, $args);
                break;

            case '/articoli':
                $this->inviaArticoli($chatId);
                break;

            case '/alert':
                $this->inviaAlert($chatId);
                break;

            case '/movimenti':
                $this->inviaMovimenti($chatId);
                break;

            default:
                TelegramBotService::sendMessage(
                    $chatId,
                    "🤖 Comando non riconosciuto.\n"
                    . "Invia /help per vedere i comandi disponibili, oppure inviami una foto."
                );
        }

        return response()->json(['ok' => true]);
    }

    private function inviaGiacenza(int $chatId, string $query)
    {
        if (empty($query)) {
            TelegramBotService::sendMessage($chatId, "Uso: `/giacenza <ricerca>`\nEsempio: `/giacenza GC1`");
            return;
        }

        $articoli = MagazzinoArticolo::where('attivo', true)
            ->where(function ($q) use ($query) {
                $q->where('codice', 'like', "%{$query}%")
                  ->orWhere('descrizione', 'like', "%{$query}%")
                  ->orWhere('categoria', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get();

        if ($articoli->isEmpty()) {
            TelegramBotService::sendMessage($chatId, "❓ Nessun articolo trovato per `{$query}`");
            return;
        }

        $righe = ["📦 *Risultati per*: `{$query}`\n"];
        foreach ($articoli as $a) {
            $qta = $a->giacenzaTotale();
            $warn = $a->sottoSoglia() ? ' ⚠️' : '';
            $righe[] = "*{$a->codice}*{$warn}\n"
                . "_{$a->descrizione}_\n"
                . "Giacenza: *{$qta} {$a->um}* (soglia min: {$a->soglia_minima})\n";
        }

        TelegramBotService::sendMessage($chatId, implode("\n", $righe));
    }

    private function inviaArticoli(int $chatId)
    {
        $articoli = MagazzinoArticolo::where('attivo', true)
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        if ($articoli->isEmpty()) {
            TelegramBotService::sendMessage($chatId, "📋 Nessun articolo in anagrafica.");
            return;
        }

        $righe = ["📋 *Ultimi 10 articoli*\n"];
        foreach ($articoli as $a) {
            $qta = $a->giacenzaTotale();
            $righe[] = "• *{$a->codice}* — {$qta} {$a->um}\n  _{$a->descrizione}_";
        }

        TelegramBotService::sendMessage($chatId, implode("\n", $righe));
    }

    private function inviaAlert(int $chatId)
    {
        $alert = MagazzinoService::alertSottoSoglia();

        if (empty($alert)) {
            TelegramBotService::sendMessage($chatId, "✅ Nessun articolo sotto soglia minima.");
            return;
        }

        $righe = ["⚠️ *Articoli sotto soglia minima*\n"];
        foreach ($alert as $a) {
            $righe[] = "• *{$a['codice']}* — {$a['giacenza']}/{$a['soglia_minima']} {$a['um']}\n  _{$a['descrizione']}_";
        }

        TelegramBotService::sendMessage($chatId, implode("\n", $righe));
    }

    private function inviaMovimenti(int $chatId)
    {
        $mov = MagazzinoMovimento::with('articolo:id,codice,descrizione')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        if ($mov->isEmpty()) {
            TelegramBotService::sendMessage($chatId, "📜 Nessun movimento registrato.");
            return;
        }

        $righe = ["📜 *Ultimi 10 movimenti*\n"];
        foreach ($mov as $m) {
            $segno = in_array($m->tipo, ['carico', 'reso']) ? '+' : '-';
            $data = $m->created_at?->format('d/m H:i') ?? '';
            $cod = $m->articolo?->codice ?? 'N/A';
            $righe[] = "• `{$data}` {$cod} {$segno}{$m->quantita} ({$m->tipo})";
        }

        TelegramBotService::sendMessage($chatId, implode("\n", $righe));
    }

    private function handleCallback(array $callback)
    {
        $callbackId = $callback['id'];
        $data = $callback['data'] ?? '';
        $chatId = $callback['message']['chat']['id'] ?? null;

        // Placeholder: i bottoni saranno usati quando si attiverà il flusso AI
        TelegramBotService::answerCallbackQuery($callbackId, 'Funzione non ancora attiva');

        Log::info('Telegram callback ricevuto', ['chat_id' => $chatId, 'data' => $data]);

        return response()->json(['ok' => true]);
    }

    private function inviaAiuto(int $chatId)
    {
        $testo = "👋 *Bot Magazzino — Grafica Nappa*\n\n"
            . "Gestisci il magazzino carta direttamente da Telegram.\n\n"
            . "*Consultazione magazzino*:\n"
            . "/giacenza <testo> — cerca articoli e mostra giacenze\n"
            . "/articoli — ultimi 10 articoli in anagrafica\n"
            . "/alert — articoli sotto soglia minima\n"
            . "/movimenti — ultimi 10 movimenti magazzino\n\n"
            . "*Diagnostica bot*:\n"
            . "/start — messaggio iniziale\n"
            . "/help — questo messaggio\n"
            . "/id — mostra il tuo chat ID\n"
            . "/ping — verifica connessione\n"
            . "/status — stato configurazione bot\n\n"
            . "*Foto*: inviando una foto viene archiviata nel MES.\n\n"
            . "*In arrivo*:\n"
            . "• Lettura automatica bolle con AI (analisi foto)\n"
            . "• Conferma dati + etichetta QR automatica\n"
            . "• Scansione QR bancale per scarico carta su commessa";

        TelegramBotService::sendMessage($chatId, $testo);
    }

    private function inviaStatus(int $chatId)
    {
        $claudeAttivo = !empty(env('ANTHROPIC_API_KEY'));
        $botConfigurato = !empty(env('TELEGRAM_BOT_TOKEN'));
        $webhookSecret = !empty(env('TELEGRAM_WEBHOOK_SECRET'));
        $allowedList = env('TELEGRAM_ALLOWED_CHATS', '(tutti)');

        $testo = "🛠 *Stato Bot*\n\n"
            . "Bot Token: " . ($botConfigurato ? '✅ configurato' : '❌ mancante') . "\n"
            . "Webhook secret: " . ($webhookSecret ? '✅ configurato' : '❌ mancante') . "\n"
            . "AI Claude Vision: " . ($claudeAttivo ? '✅ attiva' : '⏳ in attesa di attivazione') . "\n"
            . "Chat autorizzate: `{$allowedList}`";

        TelegramBotService::sendMessage($chatId, $testo);
    }
}
