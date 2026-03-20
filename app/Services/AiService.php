<?php

namespace App\Services;

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\Operatore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AiService
{
    /**
     * Provider supportati: 'groq', 'claude'
     */
    private static function getProvider(): string
    {
        return config('ai.provider', 'groq');
    }

    private static function getApiKey(): string
    {
        return config('ai.api_key', env('AI_API_KEY', ''));
    }

    /**
     * Invia un messaggio all'AI con contesto MES
     */
    public static function chat(string $messaggio, array $storico = []): string
    {
        $contesto = self::buildContext();
        $systemPrompt = self::buildSystemPrompt($contesto);

        $messaggi = [['role' => 'system', 'content' => $systemPrompt]];

        // Aggiungi storico conversazione (ultimi 10 messaggi)
        foreach (array_slice($storico, -10) as $msg) {
            $messaggi[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }

        $messaggi[] = ['role' => 'user', 'content' => $messaggio];

        $provider = self::getProvider();

        if ($provider === 'groq') {
            return self::callGroq($messaggi);
        } elseif ($provider === 'claude') {
            return self::callClaude($messaggio, $storico, $systemPrompt);
        }

        return 'Provider AI non configurato. Imposta AI_PROVIDER nel file .env';
    }

    /**
     * Costruisce il contesto dal database MES
     */
    private static function buildContext(): array
    {
        $oggi = Carbon::today();

        // KPI generali
        $fasiAttive = OrdineFase::where('stato', '<', 3)->count();
        $fasiCompletateOggi = OrdineFase::whereDate('data_fine', $oggi)->where('stato', '>=', 3)->count();
        $commesseAttive = Ordine::whereHas('fasi', fn($q) => $q->where('stato', '<', 4))->distinct('commessa')->count('commessa');
        $spediteOggi = OrdineFase::whereDate('data_fine', $oggi)->where('stato', 4)->count();

        // Commesse in ritardo (data_prevista_consegna < oggi e non completate)
        $commesseRitardo = Ordine::where('data_prevista_consegna', '<', $oggi)
            ->whereHas('fasi', fn($q) => $q->where('stato', '<', 3))
            ->with(['fasi' => fn($q) => $q->where('stato', '<', 3)])
            ->limit(20)
            ->get()
            ->map(fn($o) => [
                'commessa' => $o->commessa,
                'cliente' => $o->cliente_nome,
                'descrizione' => mb_substr($o->descrizione ?? '', 0, 40),
                'consegna' => $o->data_prevista_consegna ? (is_string($o->data_prevista_consegna) ? Carbon::parse($o->data_prevista_consegna)->format('d/m/Y') : $o->data_prevista_consegna->format('d/m/Y')) : null,
                'giorni_ritardo' => $o->data_prevista_consegna ? $oggi->diffInDays(Carbon::parse($o->data_prevista_consegna)) : 0,
                'fasi_aperte' => $o->fasi->pluck('fase')->join(', '),
            ]);

        // Fasi in corso (stato 2 = avviato)
        $fasiInCorso = OrdineFase::where('stato', 2)
            ->with(['ordine', 'faseCatalogo.reparto', 'operatori'])
            ->limit(20)
            ->get()
            ->map(fn($f) => [
                'commessa' => $f->ordine->commessa ?? '-',
                'fase' => $f->fase,
                'reparto' => optional(optional($f->faseCatalogo)->reparto)->nome ?? '-',
                'operatore' => $f->operatori->pluck('nome')->join(', '),
            ]);

        // Reparti con carico lavoro
        $caricoPerReparto = DB::table('ordine_fasi')
            ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
            ->join('reparti', 'fasi_catalogo.reparto_id', '=', 'reparti.id')
            ->where('ordine_fasi.stato', '<', 3)
            ->select('reparti.nome', DB::raw('COUNT(*) as fasi_in_coda'), DB::raw('SUM(ordine_fasi.ore) as ore_totali'))
            ->groupBy('reparti.nome')
            ->orderByDesc('fasi_in_coda')
            ->get();

        // Produttività ultimi 7 giorni
        $produttivita7gg = OrdineFase::where('stato', '>=', 3)
            ->where('data_fine', '>=', $oggi->copy()->subDays(7))
            ->count();

        // Top commesse urgenti
        $urgenti = Ordine::whereHas('fasi', fn($q) => $q->where('stato', '<', 3))
            ->whereNotNull('data_prevista_consegna')
            ->orderBy('data_prevista_consegna')
            ->limit(10)
            ->get()
            ->map(fn($o) => [
                'commessa' => $o->commessa,
                'cliente' => $o->cliente_nome,
                'consegna' => $o->data_prevista_consegna ? (is_string($o->data_prevista_consegna) ? Carbon::parse($o->data_prevista_consegna)->format('d/m/Y') : $o->data_prevista_consegna->format('d/m/Y')) : null,
                'descrizione' => mb_substr($o->descrizione ?? '', 0, 40),
            ]);

        return [
            'data_oggi' => $oggi->format('d/m/Y (l)'),
            'fasi_attive' => $fasiAttive,
            'fasi_completate_oggi' => $fasiCompletateOggi,
            'commesse_attive' => $commesseAttive,
            'spedite_oggi' => $spediteOggi,
            'produttivita_7gg' => $produttivita7gg,
            'commesse_in_ritardo' => $commesseRitardo->toArray(),
            'fasi_in_corso' => $fasiInCorso->toArray(),
            'carico_per_reparto' => $caricoPerReparto->toArray(),
            'commesse_urgenti' => $urgenti->toArray(),
        ];
    }

    /**
     * Costruisce il system prompt con i dati MES
     */
    private static function buildSystemPrompt(array $contesto): string
    {
        $json = json_encode($contesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Sei l'assistente AI del sistema MES (Manufacturing Execution System) di Grafica Nappa srl, una tipografia industriale ad Aversa (CE).

Il tuo ruolo è aiutare il titolare a prendere decisioni sulla produzione analizzando i dati in tempo reale.

REGOLE:
- Rispondi SEMPRE in italiano
- Sii conciso e diretto, usa elenchi puntati quando utile
- Quando citi dati, specifica sempre la fonte (es. "dalle fasi attive risulta che...")
- Se non hai dati sufficienti per rispondere, dillo chiaramente
- Puoi suggerire azioni concrete (es. "conviene dare priorità alla commessa X perché...")
- Usa il formato Markdown per formattare le risposte
- Non inventare dati che non sono nel contesto

CONTESTO PRODUZIONE IN TEMPO REALE:
{$json}

STRUTTURA AZIENDA:
- Reparti: Stampa offset (XL106, 24h lun-ven), Digitale, Plastificazione, Stampa a caldo (JOH), Fustella piana, Fustella/Rilievo (BOBST), Finitura digitale, Tagliacarte, Piegaincolla, Finestratura, Legatoria, Allestimento, Esterno, Spedizione
- Colli di bottiglia noti: JOH > BOBST > Piegaincolla
- Turni: XL106 24h lun-ven, altre macchine 6-22 lun-ven
- Flusso produttivo: stampa → plastificazione → caldo → fustella → piegaincolla → legatoria → allestimento → spedizione

Rispondi alle domande del titolare sulla produzione, ritardi, carichi di lavoro, performance e suggerimenti operativi.
PROMPT;
    }

    /**
     * Chiama Groq API (compatibile OpenAI)
     */
    private static function callGroq(array $messaggi): string
    {
        $apiKey = self::getApiKey();
        if (empty($apiKey)) {
            return '**Errore**: API key non configurata. Aggiungi `AI_API_KEY=gsk_...` nel file `.env`';
        }

        try {
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => config('ai.model', 'llama-3.3-70b-versatile'),
                    'messages' => $messaggi,
                    'temperature' => 0.3,
                    'max_tokens' => 2000,
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content') ?? 'Nessuna risposta dall\'AI.';
            }

            $error = $response->json('error.message') ?? $response->body();
            return "**Errore Groq** ({$response->status()}): {$error}";

        } catch (\Exception $e) {
            return '**Errore di connessione**: ' . $e->getMessage();
        }
    }

    /**
     * Chiama Claude API (Anthropic)
     */
    private static function callClaude(string $messaggio, array $storico, string $systemPrompt): string
    {
        $apiKey = self::getApiKey();
        if (empty($apiKey)) {
            return '**Errore**: API key non configurata. Aggiungi `AI_API_KEY=sk-ant-...` nel file `.env`';
        }

        $messages = [];
        foreach (array_slice($storico, -10) as $msg) {
            $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $messaggio];

        try {
            $response = Http::timeout(30)
                ->withoutVerifying()
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => config('ai.model', 'claude-sonnet-4-20250514'),
                    'max_tokens' => 2000,
                    'system' => $systemPrompt,
                    'messages' => $messages,
                ]);

            if ($response->successful()) {
                return $response->json('content.0.text') ?? 'Nessuna risposta dall\'AI.';
            }

            $error = $response->json('error.message') ?? $response->body();
            return "**Errore Claude** ({$response->status()}): {$error}";

        } catch (\Exception $e) {
            return '**Errore di connessione**: ' . $e->getMessage();
        }
    }
}
