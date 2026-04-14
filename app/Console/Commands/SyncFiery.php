<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Services\FieryService;
use App\Http\Services\FierySyncService;
use App\Models\FieryAccounting;

class SyncFiery extends Command
{
    protected $signature = 'fiery:sync';
    protected $description = 'Sincronizza stato Fiery con le fasi digitali del MES';

    public function handle(FieryService $fiery, FierySyncService $syncService)
    {
        try {
            $risultato = $syncService->sincronizza();

            if (!$risultato) {
                $this->line('Fiery: nessuna azione (idle o commessa non trovata)');
            } else {
                $this->info("Fiery sync: commessa {$risultato['commessa']} - {$risultato['fase_avviata']} avviata ({$risultato['operatore']})");
            }
        } catch (\Exception $e) {
            $this->error('Errore Fiery sync: ' . $e->getMessage());
        }

        // Sync Accounting → DB (salva job per consultazione offline)
        try {
            $this->syncAccountingToDb($fiery);
        } catch (\Exception $e) {
            $this->error('Errore sync accounting: ' . $e->getMessage());
        }

        return 0;
    }

    /**
     * Scarica i dati Accounting dal Fiery e li salva nel DB.
     * Usa updateOrCreate per non duplicare job già salvati.
     */
    protected function syncAccountingToDb(FieryService $fiery): void
    {
        if (!$fiery->isOnline()) return;

        $json = $this->fetchAccounting($fiery);
        if (!$json) return;

        $items = $json['data']['items'] ?? $json;
        if (!is_array($items)) return;

        $count = 0;
        foreach ($items as $entry) {
            $title = $entry['title'] ?? '';
            $dateStr = $entry['date'] ?? '';
            if (!$title || !$dateStr) continue;

            $dataStampa = $this->parseFieryDate($dateStr);
            if (!$dataStampa) continue;

            $fogli = (int) ($entry['total sheets printed'] ?? 0);
            $copie = (int) ($entry['copies printed'] ?? 0);
            $colore = (int) ($entry['total color pages printed'] ?? 0);
            $bn = (int) ($entry['total bw pages printed'] ?? 0);
            $mediaSize = $entry['media size'] ?? '';
            $commessa = $fiery->estraiCommessaDaTitolo($title);

            // Classifica grande/piccolo
            $tipo = 'piccolo';
            if (preg_match('/\((\d+)[\.,]\d+\s*x\s*(\d+)[\.,]\d+/', $mediaSize, $dimM)) {
                $tipo = max((int) $dimM[1], (int) $dimM[2]) > 297 ? 'grande' : 'piccolo';
            } elseif (preg_match('/^(\d+)\s*x\s*(\d+)/', $mediaSize, $dimM)) {
                $tipo = max((int) $dimM[1], (int) $dimM[2]) > 297 ? 'grande' : 'piccolo';
            } elseif (preg_match('/SRA3|A3/i', $mediaSize)) {
                $tipo = 'grande';
            }

            FieryAccounting::updateOrCreate(
                ['job_title' => $title, 'data_stampa' => $dataStampa, 'copie' => $copie],
                [
                    'commessa' => $commessa,
                    'fogli' => $fogli,
                    'pagine_colore' => $colore,
                    'pagine_bn' => $bn,
                    'formato' => $mediaSize ?: null,
                    'tipo_formato' => $tipo,
                    'stato_job' => $entry['print status'] ?? null,
                ]
            );
            $count++;
        }

        if ($count > 0) {
            $this->line("Fiery accounting: {$count} job salvati nel DB");
        }
    }

    protected function fetchAccounting(FieryService $fiery): ?array
    {
        $host = config('fiery.host');
        $baseUrl = 'https://' . $host;

        $loginR = \Illuminate\Support\Facades\Http::withoutVerifying()->timeout(5)
            ->post($baseUrl . '/live/api/v5/login', [
                'username' => config('fiery.username'),
                'password' => config('fiery.password'),
                'accessrights' => config('fiery.api_key'),
            ]);

        if (!$loginR->successful()) return null;

        $cookies = [];
        foreach ($loginR->cookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        $r = \Illuminate\Support\Facades\Http::withoutVerifying()
            ->timeout(10)
            ->withCookies($cookies, $host)
            ->get($baseUrl . '/live/api/v5/accounting');

        try {
            \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withCookies($cookies, $host)
                ->post($baseUrl . '/live/api/v5/logout');
        } catch (\Exception $e) {}

        return $r->successful() ? $r->json() : null;
    }

    protected function parseFieryDate(string $dateStr): ?string
    {
        if (empty($dateStr)) return null;
        foreach (['Y-m-d H:i:s', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i:sP', 'm/d/Y H:i:s', 'd/m/Y H:i:s', 'Y-m-d'] as $fmt) {
            $d = \DateTime::createFromFormat($fmt, $dateStr);
            if ($d) return $d->format('Y-m-d');
        }
        $ts = strtotime($dateStr);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }
}
