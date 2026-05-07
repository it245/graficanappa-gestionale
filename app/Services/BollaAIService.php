<?php

namespace App\Services;

use App\Services\Api\ClaudeApiClient;
use Illuminate\Support\Facades\Log;

/**
 * Analisi bolle di carico via API Claude (Anthropic).
 * Usa Claude Haiku 4.5 con vision per OCR strutturato DDT italiano.
 * HTTP delegato a ClaudeApiClient (DRY).
 */
class BollaAIService
{
    public static function analizzaBolla(string $imagePath): array
    {
        $client = new ClaudeApiClient();
        if (!$client->isConfigured()) {
            Log::error('ANTHROPIC_API_KEY non configurata');
            return ['ok' => false, 'error' => 'API key mancante'];
        }

        $prompt = <<<'PROMPT'
Analizza questa bolla di carico italiana (DDT - Documento di Trasporto) di CARTA ed estrai i dati strutturati.

REGOLE IMPORTANTI:
1. FORNITORE = il MITTENTE della merce (intestazione in alto, logo aziendale, sezione "Cliente" o similar DOVE "Cliente" è chi emette fattura). NON è Grafica Nappa (che è destinatario quasi sempre).
2. DESTINATARIO = "Luogo di consegna" o "Destinatario" — solitamente GRAFICA NAPPA SRL.
3. DATA DDT = data effettiva del documento (campo "del GG/MM/AA" vicino al numero DDT). IGNORA date normative tipo "D.P.R. 472 del 14 agosto 1996" (è solo riferimento legge).
4. NUMERO DDT = campo "Numero" o "Nr." vicino alla data.
5. RIGHE: una riga articolo può avere PIÙ unità di misura nella stessa riga (es: 19.488 KG e 87.456 NR = stessa carta, KG = peso, NR = numero fogli). METTI tutto in UNA riga con qta_kg e qta_fogli separati.
6. CODICE articolo = prima colonna (es: "CV380 700X820", "JW190 720X1020").
7. Numeri italiani: "19.488" = diciannovemila quattrocentoottantotto (punto = migliaia, virgola = decimali).

Rispondi SOLO con JSON valido, senza testo prima o dopo:

{
  "fornitore": "Nome mittente",
  "numero_ddt": "189",
  "data": "2026-04-21",
  "destinatario": "GRAFICA NAPPA SRL",
  "causale": "Vendita/Conto lavoro/etc",
  "righe": [
    {
      "codice": "CV380 700X820",
      "descrizione": "SUPER COSMICO GC1 Grammatura 380 Formato 70x82",
      "qta_fogli": 87456,
      "qta_kg": 19488,
      "bancali": 55
    }
  ],
  "peso_totale_kg": 19488,
  "numero_colli": 55,
  "vettore": "Nome vettore o null",
  "note": "eventuali note o null"
}

Se la foto non è una bolla leggibile, ritorna {"ok": false, "motivo": "spiegazione breve"}.
Non inventare valori. Se un campo non è leggibile, usa null. Sii preciso sui numeri (rispetta punti e virgole italiani).
PROMPT;

        $result = $client->sendVisionMessage($imagePath, $prompt, 1500, 60);

        if (!$result['ok']) {
            return ['ok' => false, 'error' => $result['error'] ?? 'Errore API'];
        }

        $text = $result['text'] ?? '';
        if (empty($text)) {
            return ['ok' => false, 'error' => 'Risposta vuota da Claude'];
        }

        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($text));
        $parsed = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Claude response non JSON', ['raw' => $text]);
            return ['ok' => false, 'error' => 'JSON invalido', 'raw' => $text];
        }

        if (isset($parsed['ok']) && $parsed['ok'] === false) {
            return $parsed;
        }

        $parsed['ok'] = true;
        $parsed['_usage'] = $result['usage'] ?? null;
        $parsed['_model'] = $result['model'] ?? null;

        Log::info('Bolla analizzata', [
            'fornitore' => $parsed['fornitore'] ?? null,
            'numero_ddt' => $parsed['numero_ddt'] ?? null,
            'righe' => count($parsed['righe'] ?? []),
        ]);

        return $parsed;
    }

    public static function formatPerTelegram(array $data): string
    {
        if (!($data['ok'] ?? false)) {
            $motivo = $data['motivo'] ?? $data['error'] ?? 'errore sconosciuto';
            return "❌ Bolla non leggibile.\n_{$motivo}_";
        }

        $fmt = fn($n) => is_numeric($n) ? number_format((float)$n, 0, ',', '.') : '—';

        $out = "📄 *Bolla analizzata*\n\n";
        $out .= "*Fornitore:* " . ($data['fornitore'] ?? '—') . "\n";
        $out .= "*DDT:* " . ($data['numero_ddt'] ?? '—');
        $out .= " del " . ($data['data'] ?? '—') . "\n";
        if (!empty($data['causale'])) {
            $out .= "*Causale:* " . $data['causale'] . "\n";
        }
        if (!empty($data['destinatario'])) {
            $out .= "*A:* " . $data['destinatario'] . "\n";
        }

        $out .= "\n*Articoli:*\n";
        foreach (($data['righe'] ?? []) as $r) {
            $cod = !empty($r['codice']) ? "`{$r['codice']}`\n" : '';
            $desc = $r['descrizione'] ?? '—';
            $parts = [];
            if (!empty($r['qta_fogli'])) $parts[] = $fmt($r['qta_fogli']) . ' fg';
            if (!empty($r['qta_kg'])) $parts[] = $fmt($r['qta_kg']) . ' kg';
            if (!empty($r['bancali'])) $parts[] = $r['bancali'] . ' bcl';
            $qtaStr = implode(' · ', $parts) ?: '—';
            $out .= "• {$cod}{$desc}\n  _{$qtaStr}_\n";
        }

        $foot = [];
        if (!empty($data['peso_totale_kg'])) $foot[] = "Peso tot: " . $fmt($data['peso_totale_kg']) . " kg";
        if (!empty($data['numero_colli'])) $foot[] = "Colli: " . $data['numero_colli'];
        if (!empty($data['vettore'])) $foot[] = "Vettore: " . $data['vettore'];
        if ($foot) {
            $out .= "\n" . implode(' · ', $foot);
        }
        return $out;
    }
}
