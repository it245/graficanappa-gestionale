<?php

namespace App\Http\Controllers;

use App\Models\MagazzinoArticolo;
use App\Models\MagazzinoMovimento;
use App\Services\MagazzinoService;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

        $this->processUpdate($request->all());
        return response()->json(['ok' => true]);
    }

    /**
     * Processa un singolo update Telegram.
     * Usato sia dal webhook HTTP che dal comando artisan di polling.
     */
    public function processUpdate(array $update): void
    {
        Log::info('Telegram update ricevuto', ['keys' => array_keys($update)]);

        // Callback query (click bottoni inline)
        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);
            return;
        }

        $message = $update['message'] ?? null;
        if (!$message) return;

        $chatId = $message['chat']['id'] ?? null;
        if (!$chatId) return;

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
            return;
        }

        // Foto allegata
        if (isset($message['photo'])) {
            $this->handleFoto($chatId, $message);
            return;
        }

        // Messaggio testuale
        if (isset($message['text'])) {
            $this->handleText($chatId, $message['text']);
            return;
        }
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

        $kb = round(filesize($imagePath) / 1024, 1);
        $msgIniziale = "📸 *Foto ricevuta* (`{$filename}`, {$kb} KB)\n\n🤖 Analisi AI in corso…";
        $sent = TelegramBotService::sendMessage($chatId, $msgIniziale);
        $messageId = $sent['result']['message_id'] ?? null;

        if (!env('ANTHROPIC_API_KEY')) {
            $fallback = "📸 *Foto salvata*\n\nFile: `{$filename}`\nDimensione: {$kb} KB\n\n_AI non ancora configurata. Foto archiviata per analisi futura._";
            if ($messageId) {
                TelegramBotService::editMessage($chatId, $messageId, $fallback);
            } else {
                TelegramBotService::sendMessage($chatId, $fallback);
            }
            return response()->json(['ok' => true]);
        }

        $result = \App\Services\BollaAIService::analizzaBolla($imagePath);
        $testo = \App\Services\BollaAIService::formatPerTelegram($result);

        if ($result['ok'] ?? false) {
            Log::info('Bolla analizzata', [
                'chat_id' => $chatId,
                'file' => $filename,
                'fornitore' => $result['fornitore'] ?? null,
                'ddt' => $result['numero_ddt'] ?? null,
                'righe' => count($result['righe'] ?? []),
                'usage' => $result['_usage'] ?? null,
            ]);

            $caricoReport = $this->registraCarichiDaBolla($result, $imagePath);
            if ($caricoReport !== '') {
                $testo .= "\n\n" . $caricoReport;
            }
        } else {
            Log::warning('Bolla analisi fallita', ['chat_id' => $chatId, 'file' => $filename, 'error' => $result['error'] ?? 'unknown']);
        }

        if ($messageId) {
            TelegramBotService::editMessage($chatId, $messageId, $testo);
        } else {
            TelegramBotService::sendMessage($chatId, $testo);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Per ogni riga della bolla, cerca match in magazzino_articoli per codice.
     * Se match → registra carico con qta_fogli.
     * Se no match → log pending.
     */
    private function registraCarichiDaBolla(array $bolla, string $imagePath): string
    {
        $righe = $bolla['righe'] ?? [];
        if (empty($righe)) return '';

        $fornitore = $bolla['fornitore'] ?? null;
        $ddt = $bolla['numero_ddt'] ?? null;
        $dataDdt = $bolla['data'] ?? null;
        $lotto = $ddt ? "DDT{$ddt}-" . ($dataDdt ?? date('Y-m-d')) : null;
        $ocrRaw = json_encode($bolla, JSON_UNESCAPED_UNICODE);
        $fotoPath = 'bolle/' . basename($imagePath);

        $caricati = [];
        $mancanti = [];

        foreach ($righe as $r) {
            $codice = trim((string)($r['codice'] ?? ''));
            $qtaFogli = (int)($r['qta_fogli'] ?? 0);
            if ($codice === '' || $qtaFogli <= 0) continue;

            $articolo = \App\Models\MagazzinoArticolo::where('codice', $codice)->first();

            if (!$articolo) {
                $mancanti[] = $codice;
                continue;
            }

            try {
                \App\Services\MagazzinoService::registraCarico([
                    'articolo_id' => $articolo->id,
                    'quantita' => $qtaFogli,
                    'lotto' => $lotto,
                    'fornitore' => $fornitore,
                    'foto_bolla' => $fotoPath,
                    'ocr_raw' => $ocrRaw,
                    'note' => "Carico da bolla Telegram DDT {$ddt}",
                ]);
                $caricati[] = "{$codice} (+" . number_format($qtaFogli, 0, ',', '.') . " fg)";
            } catch (\Exception $e) {
                Log::error('Carico bolla fallito', ['codice' => $codice, 'error' => $e->getMessage()]);
                $mancanti[] = $codice . ' (errore)';
            }
        }

        $out = '';
        if ($caricati) {
            $out .= "✅ *Caricato in magazzino:*\n" . implode("\n", array_map(fn($c) => "• {$c}", $caricati));
        }
        if ($mancanti) {
            if ($out) $out .= "\n\n";
            $out .= "⚠️ *Articolo non trovato in anagrafica:*\n" . implode("\n", array_map(fn($c) => "• {$c}", $mancanti));
            $out .= "\n_Aggiungilo in Magazzino → Articoli per caricare la prossima volta._";
        }
        return $out;
    }

    private function handleText(int $chatId, string $text)
    {
        $text = trim($text);

        // Se l'utente è in mezzo a un flusso /carico, processa lo step corrente
        $stateKey = "tg_carico_state_{$chatId}";
        if (Cache::has($stateKey) && !Str::startsWith($text, '/')) {
            return $this->processaStepCarico($chatId, $text, $stateKey);
        }

        // /annulla interrompe qualsiasi flusso attivo
        if (strtolower($text) === '/annulla' || strtolower($text) === '/cancel') {
            Cache::forget($stateKey);
            TelegramBotService::sendMessage($chatId, "❌ Operazione annullata.");
            return response()->json(['ok' => true]);
        }

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

            case '/carico':
                $this->avviaCarico($chatId);
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
        $messageId = $callback['message']['message_id'] ?? null;

        Log::info('Telegram callback ricevuto', ['chat_id' => $chatId, 'data' => $data]);

        if (!$chatId) {
            TelegramBotService::answerCallbackQuery($callbackId);
            return response()->json(['ok' => true]);
        }

        [$action, $arg] = array_pad(explode(':', $data, 2), 2, null);

        switch ($action) {
            case 'carico_conferma':
                TelegramBotService::answerCallbackQuery($callbackId, 'Salvataggio in corso...');
                $this->confermaCarico($chatId, $messageId);
                break;

            case 'carico_annulla':
                TelegramBotService::answerCallbackQuery($callbackId, 'Annullato');
                Cache::forget("tg_carico_state_{$chatId}");
                if ($messageId) TelegramBotService::editMessage($chatId, $messageId, '❌ Registrazione annullata.');
                break;

            default:
                TelegramBotService::answerCallbackQuery($callbackId, 'Azione non riconosciuta');
        }

        return response()->json(['ok' => true]);
    }

    /* ============================================================
     *  FSM /carico — registrazione carta manuale via conversazione
     * ============================================================
     */

    /** Sequenza campi del flusso /carico */
    private const CARICO_STEPS = [
        'fornitore'    => ['label' => '🏭 *Fornitore?*', 'required' => true],
        'categoria'    => ['label' => "📦 *Categoria?*\n_Es: GC1, GC2, Patinata, Uncoated, Cartoncino, Altro_", 'required' => true],
        'descrizione'  => ['label' => '📝 *Descrizione completa?*\n_Es: Performa White 56x102_', 'required' => true],
        'grammatura'   => ['label' => '⚖️ *Grammatura (g)?*\n_Numero, es: 300_', 'required' => true, 'type' => 'int'],
        'formato'      => ['label' => '📐 *Formato?*\n_Es: 56x102_', 'required' => false],
        'quantita'     => ['label' => '🔢 *Quantità (fogli)?*\n_Numero intero_', 'required' => true, 'type' => 'int'],
        'lotto'        => ['label' => '🏷 *Numero lotto?*\n_/skip per saltare_', 'required' => false],
    ];

    private function avviaCarico(int $chatId)
    {
        $stateKey = "tg_carico_state_{$chatId}";
        $firstStep = array_key_first(self::CARICO_STEPS);

        Cache::put($stateKey, [
            'step' => $firstStep,
            'data' => [],
        ], now()->addMinutes(30));

        $introduzione = "🧾 *Registrazione carico*\n"
            . "_Rispondi a ogni domanda. Scrivi /annulla per interrompere._\n\n"
            . self::CARICO_STEPS[$firstStep]['label'];

        TelegramBotService::sendMessage($chatId, $introduzione);
    }

    private function processaStepCarico(int $chatId, string $text, string $stateKey)
    {
        $state = Cache::get($stateKey);
        if (!$state) return response()->json(['ok' => true]);

        $currentStep = $state['step'];
        $config = self::CARICO_STEPS[$currentStep] ?? null;
        if (!$config) {
            Cache::forget($stateKey);
            TelegramBotService::sendMessage($chatId, "⚠️ Stato corrotto, ricomincia con /carico");
            return response()->json(['ok' => true]);
        }

        // /skip per campi opzionali
        if (strtolower(trim($text)) === '/skip') {
            if (!empty($config['required'])) {
                TelegramBotService::sendMessage($chatId, "⚠️ Campo obbligatorio — non si può saltare.");
                return response()->json(['ok' => true]);
            }
            $state['data'][$currentStep] = null;
        } else {
            // Validazione tipo
            if (($config['type'] ?? null) === 'int') {
                $numeric = preg_replace('/[^\d]/', '', $text);
                if ($numeric === '') {
                    TelegramBotService::sendMessage($chatId, "⚠️ Serve un numero. Riprova.");
                    return response()->json(['ok' => true]);
                }
                $state['data'][$currentStep] = (int) $numeric;
            } else {
                $val = trim($text);
                if (empty($val) && !empty($config['required'])) {
                    TelegramBotService::sendMessage($chatId, "⚠️ Campo obbligatorio. Riprova.");
                    return response()->json(['ok' => true]);
                }
                $state['data'][$currentStep] = $val;
            }
        }

        // Avanza al campo successivo
        $steps = array_keys(self::CARICO_STEPS);
        $currentIndex = array_search($currentStep, $steps);
        $nextStep = $steps[$currentIndex + 1] ?? null;

        if ($nextStep) {
            $state['step'] = $nextStep;
            Cache::put($stateKey, $state, now()->addMinutes(30));
            TelegramBotService::sendMessage($chatId, self::CARICO_STEPS[$nextStep]['label']);
        } else {
            // Ultimo step completato → mostra riepilogo + bottoni
            Cache::put($stateKey, $state, now()->addMinutes(30));
            $this->mostraRiepilogoCarico($chatId, $state['data']);
        }

        return response()->json(['ok' => true]);
    }

    private function mostraRiepilogoCarico(int $chatId, array $data)
    {
        $testo = "📋 *Riepilogo carico*\n\n"
            . "🏭 *Fornitore*: " . ($data['fornitore'] ?? '—') . "\n"
            . "📦 *Categoria*: " . ($data['categoria'] ?? '—') . "\n"
            . "📝 *Descrizione*: " . ($data['descrizione'] ?? '—') . "\n"
            . "⚖️ *Grammatura*: " . ($data['grammatura'] ?? '—') . "g\n"
            . "📐 *Formato*: " . ($data['formato'] ?: '—') . "\n"
            . "🔢 *Quantità*: " . ($data['quantita'] ?? '—') . " fg\n"
            . "🏷 *Lotto*: " . ($data['lotto'] ?: '—') . "\n\n"
            . "Confermare il salvataggio?";

        TelegramBotService::sendMessageWithButtons($chatId, $testo, [
            [
                ['text' => '✅ Conferma', 'callback_data' => 'carico_conferma'],
                ['text' => '❌ Annulla', 'callback_data' => 'carico_annulla'],
            ],
        ]);
    }

    private function confermaCarico(int $chatId, ?int $messageId)
    {
        $stateKey = "tg_carico_state_{$chatId}";
        $state = Cache::get($stateKey);
        if (!$state) {
            TelegramBotService::sendMessage($chatId, "⚠️ Sessione scaduta, ricomincia con /carico");
            return;
        }

        $data = $state['data'];

        try {
            // Genera codice articolo canonico: CATEGORIA.INIZIALI.GRAMMATURA.FORMATO
            $sigla = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $data['descrizione'] ?? ''), 0, 2));
            $sigla = $sigla ?: 'XX';
            $formatoKey = preg_replace('/\s+/', '', $data['formato'] ?? '');
            $codice = implode('.', array_filter([
                strtoupper($data['categoria'] ?? 'GEN'),
                $sigla,
                (string)($data['grammatura'] ?? ''),
                $formatoKey,
            ]));

            // Trova o crea articolo
            $articolo = MagazzinoArticolo::firstOrCreate(
                ['codice' => $codice],
                [
                    'descrizione' => $data['descrizione'] ?? 'Articolo da bot',
                    'categoria' => $data['categoria'] ?? null,
                    'formato' => $data['formato'] ?? null,
                    'grammatura' => $data['grammatura'] ?? null,
                    'um' => 'fg',
                    'soglia_minima' => 0,
                    'fornitore' => $data['fornitore'] ?? null,
                    'attivo' => true,
                ]
            );

            // Registra movimento carico
            MagazzinoService::registraCarico([
                'articolo_id' => $articolo->id,
                'quantita' => $data['quantita'],
                'lotto' => $data['lotto'] ?: null,
                'fornitore' => $data['fornitore'] ?? null,
                'note' => 'Caricato via Bot Telegram',
            ]);

            Cache::forget($stateKey);

            $giacenza = $articolo->fresh()->giacenzaTotale();

            $riepilogo = "✅ *Carico registrato*\n\n"
                . "Codice articolo: `{$articolo->codice}`\n"
                . "Aggiunti: *{$data['quantita']} fg*\n"
                . "Giacenza totale: *{$giacenza} fg*";

            if ($messageId) {
                TelegramBotService::editMessage($chatId, $messageId, $riepilogo);
            } else {
                TelegramBotService::sendMessage($chatId, $riepilogo);
            }
        } catch (\Exception $e) {
            Log::error('Carico Telegram errore', ['error' => $e->getMessage(), 'data' => $data]);
            TelegramBotService::sendMessage($chatId, "❌ Errore nel salvataggio: " . $e->getMessage());
        }
    }

    private function inviaAiuto(int $chatId)
    {
        $testo = "👋 *Bot Magazzino — Grafica Nappa*\n\n"
            . "Gestisci il magazzino carta direttamente da Telegram.\n\n"
            . "*Registra carico*:\n"
            . "/carico — avvia registrazione guidata di una carta in ingresso\n"
            . "/annulla — interrompe un flusso in corso\n\n"
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
