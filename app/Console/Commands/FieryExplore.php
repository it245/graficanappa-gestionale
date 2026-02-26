<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FieryExplore extends Command
{
    protected $signature = 'fiery:explore {--search= : Cerca job per nome (es. 66590)}';
    protected $description = 'Esplora gli endpoint Fiery API e mostra i dati disponibili';

    public function handle()
    {
        $host = config('fiery.host');
        $baseUrl = 'https://' . $host;
        $search = $this->option('search');

        $this->info("=== FIERY EXPLORE: {$host} ===\n");

        // Login API v5
        $this->info('--- Login API v5 ---');
        $cookies = null;
        try {
            $loginR = Http::withoutVerifying()->timeout(15)->post($baseUrl . '/live/api/v5/login', [
                'username' => config('fiery.username'),
                'password' => config('fiery.password'),
                'accessrights' => config('fiery.api_key'),
            ]);
            if ($loginR->successful()) {
                $this->info('Login OK');
                $cookies = $loginR->cookies();
            } else {
                $this->error("Login fallito: HTTP {$loginR->status()}");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Login: ' . $e->getMessage());
            return 1;
        }

        $cookieArray = [];
        foreach ($cookies as $cookie) {
            $cookieArray[$cookie->getName()] = $cookie->getValue();
        }
        $authed = fn() => Http::withoutVerifying()->timeout(60)->withCookies($cookieArray, $host);

        // =============================
        // 1. Jobs API v5 - riepilogo
        // =============================
        $this->info("\n--- 1. API v5 Jobs ---");
        try {
            $r = $authed()->get($baseUrl . '/live/api/v5/jobs');
            if ($r->successful()) {
                $json = $r->json();
                $items = $json['data']['items'] ?? $json;
                $this->line("Totale job nella lista: " . count($items));

                $this->line(str_pad('TITLE', 55) . str_pad('STATE', 18) . str_pad('COPIE', 12) . 'FOGLI');
                $this->line(str_repeat('-', 100));
                foreach ($items as $job) {
                    $title = substr($job['title'] ?? '?', 0, 53);
                    $state = $job['state'] ?? '?';
                    $copie = ($job['copies printed'] ?? '?') . '/' . ($job['num copies'] ?? '?');
                    $fogli = $job['total sheets printed'] ?? '?';
                    $this->line(str_pad($title, 55) . str_pad($state, 18) . str_pad($copie, 12) . $fogli);
                }

                // Se c'è ricerca, mostra i job filtrati
                if ($search) {
                    $filtered = collect($items)->filter(fn($j) => stripos($j['title'] ?? '', $search) !== false);
                    $this->info("\nJob che contengono '{$search}': {$filtered->count()}");
                    foreach ($filtered as $job) {
                        $this->line(json_encode(array_intersect_key($job, array_flip([
                            'id', 'title', 'state', 'copies printed', 'num copies',
                            'total sheets printed', 'total pages printed', 'date',
                        ])), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    }
                }
            } else {
                $this->error("HTTP {$r->status()}");
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        // =============================
        // 2. Job Log API v5 - storico stampe
        // =============================
        $this->info("\n--- 2. API v5 Job Log ---");
        $endpoints = [
            '/live/api/v5/joblog',
            '/live/api/v5/printedlog',
            '/live/api/v5/jobs/log',
            '/live/api/v5/jobs/printed',
        ];
        foreach ($endpoints as $ep) {
            try {
                $r = $authed()->get($baseUrl . $ep);
                $status = $r->status();
                if ($r->successful()) {
                    $body = $r->body();
                    $this->info("{$ep} => HTTP {$status} (" . strlen($body) . " bytes)");
                    // Decodifica e mostra struttura
                    $json = json_decode($body, true);
                    if ($json) {
                        $count = isset($json['data']['items']) ? count($json['data']['items']) : (is_array($json) ? count($json) : '?');
                        $this->line("  Entries: {$count}");
                        // Mostra primo entry per capire la struttura
                        $first = $json['data']['items'][0] ?? ($json[0] ?? null);
                        if ($first) {
                            $this->line("  Struttura primo entry (chiavi): " . implode(', ', array_keys($first)));
                            if ($search) {
                                // Cerca nel job log
                                $allItems = $json['data']['items'] ?? $json;
                                $found = collect($allItems)->filter(fn($j) => stripos(json_encode($j), $search) !== false);
                                $this->info("  Entries con '{$search}': {$found->count()}");
                                foreach ($found->take(5) as $entry) {
                                    $this->line(json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                                }
                            }
                        }
                    } else {
                        $this->line("  Preview: " . substr($body, 0, 500));
                    }
                } else {
                    $this->warn("{$ep} => HTTP {$status}");
                }
            } catch (\Exception $e) {
                $this->warn("{$ep} => Errore: " . $e->getMessage());
            }
        }

        // =============================
        // 3. Accounting API v5 - cerca run storici
        // =============================
        $this->info("\n--- 3. API v5 Accounting ---");
        try {
            ini_set('memory_limit', '256M');
            $r = $authed()->timeout(60)->get($baseUrl . '/live/api/v5/accounting');
            if ($r->successful()) {
                $json = $r->json();
                $items = $json['data']['items'] ?? $json;
                $count = is_array($items) ? count($items) : '?';
                $this->line("Totale entries: {$count}");

                if (is_array($items) && count($items) > 0) {
                    // Struttura
                    $first = $items[0];
                    $this->line("Chiavi primo entry: " . implode(', ', array_keys($first)));
                    $this->line("Primo entry:");
                    $this->line(json_encode($first, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                    if ($search) {
                        // Filtra per la commessa cercata
                        $found = collect($items)->filter(fn($j) => stripos(json_encode($j), $search) !== false);
                        $this->info("\nAccounting entries con '{$search}': {$found->count()}");
                        $totFogli = 0;
                        $totCopie = 0;
                        foreach ($found as $entry) {
                            $titolo = $entry['title'] ?? $entry['job title'] ?? $entry['name'] ?? '?';
                            $fogli = $entry['total sheets printed'] ?? $entry['sheets'] ?? $entry['total sheets'] ?? 0;
                            $copie = $entry['copies printed'] ?? $entry['copies'] ?? 0;
                            $data = $entry['date'] ?? $entry['print date'] ?? '?';
                            $stato = $entry['state'] ?? $entry['status'] ?? '?';
                            $totFogli += (int) $fogli;
                            $totCopie += (int) $copie;
                            $this->line("  {$titolo} | Copie: {$copie} | Fogli: {$fogli} | {$stato} | {$data}");
                        }
                        $this->info("TOTALE per '{$search}': {$totCopie} copie, {$totFogli} fogli");
                    }
                }
            } else {
                $this->error("HTTP {$r->status()}");
            }
        } catch (\Exception $e) {
            $this->error('Accounting: ' . $e->getMessage());
        }

        // =============================
        // 4. WebTools endpoints (no auth)
        // =============================
        $this->info("\n--- 4. WebTools Endpoints ---");
        $wtEndpoints = [
            '/wt4/home/get_job_list' => ['client_locale' => 'it_IT'],
            '/wt4/home/get_printed_list' => ['client_locale' => 'it_IT'],
            '/wt4/home/get_job_log' => ['client_locale' => 'it_IT'],
            '/wt4/home/get_history' => ['client_locale' => 'it_IT'],
            '/wt4/jobs/get_all_jobs' => [],
            '/wt4/jobs/get_printed_jobs' => [],
            '/wt4/jobs/get_job_history' => [],
        ];
        foreach ($wtEndpoints as $ep => $params) {
            try {
                $r = Http::withoutVerifying()->timeout(15)->get($baseUrl . $ep, $params);
                $status = $r->status();
                if ($r->successful()) {
                    $body = $r->body();
                    $this->info("{$ep} => HTTP {$status} (" . strlen($body) . " bytes)");
                    $preview = substr($body, 0, 800);
                    $this->line("  Preview: {$preview}");
                } else {
                    $this->warn("{$ep} => HTTP {$status}");
                }
            } catch (\Exception $e) {
                $this->warn("{$ep} => " . $e->getMessage());
            }
        }

        // Logout
        try { $authed()->post($baseUrl . '/live/api/v5/logout'); } catch (\Exception $e) {}

        $this->info("\n=== FINE ESPLORAZIONE ===");
        return 0;
    }
}
