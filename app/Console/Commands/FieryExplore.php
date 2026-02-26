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
        $http = Http::withoutVerifying()->timeout(15);

        $this->info("=== FIERY EXPLORE: {$host} ===\n");

        // 1. WebTools status (no auth)
        $this->info('--- 1. WebTools Server Status (no auth) ---');
        try {
            $r = $http->get($baseUrl . '/wt4/home/get_server_status', ['client_locale' => 'it_IT']);
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
            $loginR = $http->post($baseUrl . '/live/api/v5/login', [
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

        $authed = fn() => $http->withCookies($cookies, $host);

        // 3. Status API
        $this->info("\n--- 3. API v5 Status ---");
        try {
            $r = $authed()->get($baseUrl . '/live/api/v5/status');
            $this->line($r->successful() ? json_encode($r->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : "HTTP {$r->status()}");
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        // 4. Jobs list
        $this->info("\n--- 4. API v5 Jobs (lista completa) ---");
        try {
            $r = $authed()->get($baseUrl . '/live/api/v5/jobs');
            if ($r->successful()) {
                $jobs = $r->json();
                $this->line("Totale job: " . count($jobs));
                // Mostra i primi 5 job con tutti i campi
                $sample = array_slice($jobs, 0, 5);
                $this->line(json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->error("HTTP {$r->status()}: " . $r->body());
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        // 5. Printed jobs
        $this->info("\n--- 5. API v5 Jobs Printed ---");
        try {
            $r = $authed()->get($baseUrl . '/live/api/v5/jobs', ['state' => 'printed']);
            if ($r->successful()) {
                $jobs = $r->json();
                $this->line("Job stampati: " . count($jobs));
                $sample = array_slice($jobs, 0, 3);
                $this->line(json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->error("HTTP {$r->status()}");
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        // 6. Processing jobs
        $this->info("\n--- 6. API v5 Jobs Processing ---");
        try {
            $r = $authed()->get($baseUrl . '/live/api/v5/jobs', ['state' => 'processing']);
            if ($r->successful()) {
                $this->line(json_encode($r->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->error("HTTP {$r->status()}");
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        // 7. Printers/devices
        $this->info("\n--- 7. API v5 Printers ---");
        try {
            $r = $authed()->get($baseUrl . '/live/api/v5/printers');
            if ($r->successful()) {
                $this->line(json_encode($r->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->error("HTTP {$r->status()}");
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        // 8. Job accounting/log
        $this->info("\n--- 8. API v5 Accounting ---");
        try {
            $r = $authed()->get($baseUrl . '/live/api/v5/accounting');
            if ($r->successful()) {
                $data = $r->json();
                $this->line("Entries: " . count($data));
                $sample = array_slice($data, 0, 3);
                $this->line(json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->error("HTTP {$r->status()}: " . substr($r->body(), 0, 200));
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        // 9. Job log
        $this->info("\n--- 9. API v5 Job Log ---");
        try {
            $r = $authed()->get($baseUrl . '/live/api/v5/joblog');
            if ($r->successful()) {
                $data = $r->json();
                $this->line("Log entries: " . (is_array($data) ? count($data) : 'N/A'));
                $sample = is_array($data) ? array_slice($data, 0, 3) : $data;
                $this->line(json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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
