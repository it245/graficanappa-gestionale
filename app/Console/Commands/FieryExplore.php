<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FieryExplore extends Command
{
    protected $signature = 'fiery:explore';
    protected $description = 'Esplora gli endpoint Fiery API e mostra i dati disponibili';

    public function handle()
    {
        $host = config('fiery.host');
        $baseUrl = 'https://' . $host;

        $this->info("=== FIERY EXPLORE: {$host} ===\n");

        // 1. WebTools status (no auth)
        $this->info('--- 1. WebTools Server Status (no auth) ---');
        try {
            $r = Http::withoutVerifying()->timeout(15)->get($baseUrl . '/wt4/home/get_server_status', ['client_locale' => 'it_IT']);
            if ($r->successful()) {
                $data = $r->json();
                $this->line(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->error("HTTP {$r->status()}");
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        // 2. Login API v5
        $this->info("\n--- 2. Login API v5 ---");
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
                $this->line($loginR->body());
            }
        } catch (\Exception $e) {
            $this->error('Login: ' . $e->getMessage());
        }

        if (!$cookies) {
            $this->error('Impossibile proseguire senza login');
            return 1;
        }

        // Converti CookieJar in array associativo per withCookies()
        $cookieArray = [];
        foreach ($cookies as $cookie) {
            $cookieArray[$cookie->getName()] = $cookie->getValue();
        }

        $authed = fn() => Http::withoutVerifying()->timeout(30)->withCookies($cookieArray, $host);

        // 3. Jobs list - riepilogo tabellare
        $this->info("\n--- 3. API v5 Jobs (tutti) ---");
        try {
            $r = $authed()->get($baseUrl . '/live/api/v5/jobs');
            if ($r->successful()) {
                $json = $r->json();
                $items = $json['data']['items'] ?? $json;
                $total = $json['data']['totalItems'] ?? count($items);
                $this->line("Totale job: {$total}");

                // Tabella riepilogativa
                $this->info("\nRiepilogo job:");
                $this->line(str_pad('TITLE', 55) . str_pad('STATE', 18) . str_pad('COPIE', 12) . 'FOGLI');
                $this->line(str_repeat('-', 100));

                foreach ($items as $job) {
                    $title = substr($job['title'] ?? '?', 0, 53);
                    $state = $job['state'] ?? $job['status'] ?? '?';
                    $copieStampate = $job['copies printed'] ?? '?';
                    $copieTotali = $job['num copies'] ?? '?';
                    $fogliStampati = $job['total sheets printed'] ?? '?';

                    $this->line(
                        str_pad($title, 55) .
                        str_pad($state, 18) .
                        str_pad("{$copieStampate}/{$copieTotali}", 12) .
                        $fogliStampati
                    );
                }

                // Mostra dettaglio del job in stampa (se presente)
                $printing = collect($items)->firstWhere('state', 'printing');
                if ($printing) {
                    $this->info("\n--- Job in stampa (dettaglio) ---");
                    // Mostra solo i campi utili
                    $campiUtili = [
                        'id', 'title', 'username', 'state', 'status',
                        'copies printed', 'num copies',
                        'total pages printed', 'total sheets printed',
                        'total color pages printed', 'total bw pages printed',
                        'media size', 'media weight', 'media type',
                        'input slot', 'EFDuplex', 'date',
                    ];
                    $dettaglio = array_intersect_key($printing, array_flip($campiUtili));
                    $this->line(json_encode($dettaglio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            } else {
                $this->error("HTTP {$r->status()}: " . substr($r->body(), 0, 300));
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        // 4. Accounting - solo conteggio, senza stampare il body enorme
        $this->info("\n--- 4. API v5 Accounting (solo conteggio) ---");
        try {
            // Usa streaming per evitare OOM: scarica solo i primi bytes per capire la struttura
            $r = $authed()->get($baseUrl . '/live/api/v5/accounting');
            if ($r->successful()) {
                // Prendi solo i primi 2000 chars per evitare OOM
                $bodyPreview = substr($r->body(), 0, 2000);
                $this->line("Accounting disponibile (response troppo grande per stampare tutto)");
                $this->line("Preview primi 2000 chars:");
                $this->line($bodyPreview . "\n...(troncato)");
            } else {
                $this->error("HTTP {$r->status()}");
            }
        } catch (\Exception $e) {
            $this->error('Accounting: ' . $e->getMessage());
        }

        // 5. Job log - solo primi 3
        $this->info("\n--- 5. API v5 Job Log (primi 3) ---");
        try {
            $r = $authed()->get($baseUrl . '/live/api/v5/joblog');
            if ($r->successful()) {
                $bodyPreview = substr($r->body(), 0, 2000);
                $this->line("Preview primi 2000 chars:");
                $this->line($bodyPreview . "\n...(troncato)");
            } else {
                $this->error("HTTP {$r->status()}: " . substr($r->body(), 0, 200));
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        // Logout
        try {
            $authed()->post($baseUrl . '/live/api/v5/logout');
        } catch (\Exception $e) {}

        $this->info("\n=== FINE ESPLORAZIONE ===");
        return 0;
    }
}
