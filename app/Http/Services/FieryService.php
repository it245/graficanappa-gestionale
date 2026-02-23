<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FieryService
{
    protected $host;
    protected $baseUrl;
    protected $username;
    protected $password;
    protected $apiKey;
    protected $timeout;

    public function __construct()
    {
        $this->host = config('fiery.host');
        $this->baseUrl = 'https://' . $this->host;
        $this->username = config('fiery.username');
        $this->password = config('fiery.password');
        $this->apiKey = config('fiery.api_key');
        $this->timeout = config('fiery.timeout', 15);
    }

    private function http()
    {
        return Http::withoutVerifying()->timeout($this->timeout);
    }

    /**
     * Stato completo dal WebTools (sempre disponibile, no auth)
     */
    public function getServerStatus(): ?array
    {
        try {
            $response = $this->http()->get($this->baseUrl . '/wt4/home/get_server_status', [
                'client_locale' => 'it_IT',
            ]);

            if ($response->successful()) {
                return $this->parseServerStatus($response->json());
            }
        } catch (\Exception $e) {
            // Fiery non raggiungibile
        }

        return null;
    }

    /**
     * Parsa la risposta del WebTools in formato standard
     */
    private function parseServerStatus(array $raw): array
    {
        $device = $raw['device_status'][0] ?? [];

        // Stato macchina
        $printState = $device['print_state'] ?? 'UNKNOWN';
        $deviceState = $device['device_state'] ?? 'UNKNOWN';

        $stato = 'offline';
        if ($printState === 'STATE_PRINTING') {
            $stato = 'stampa';
        } elseif ($printState === 'STATE_IDLE' || $printState === 'IDLE') {
            $stato = 'idle';
        } elseif (!empty($device['print_docname'])) {
            $stato = 'stampa';
        }

        // Avvisi/errori
        $avviso = $device['statusString'] ?? null;
        if ($deviceState === 'STR_ERROR') {
            $stato = 'errore';
        } elseif ($deviceState === 'STR_WARNING') {
            // mantieni lo stato corrente ma segna l'avviso
        }

        // RIP (elaborazione)
        $ripIdle = $raw['isRipIdle'] ?? true;
        $ripDoc = $raw['rip_docname'] ?? null;
        $ripCount = $raw['rip_count'] ?? 0;
        if ($ripDoc === 'None') $ripDoc = null;

        // Job in stampa
        $printDoc = $device['print_docname'] ?? null;
        $printCopies = $device['print_count_string'] ?? null;
        $printUser = $device['print_username'] ?? null;
        $pageCount = $device['page_count'] ?? 0;
        $printCount = $device['print_count'] ?? 0;
        $printTotal = $device['print_total_count'] ?? 0;

        return [
            'stato' => $stato,
            'avviso' => $avviso,
            'ultimo_aggiornamento' => $raw['last_updated_time'] ?? null,
            'rip' => [
                'idle' => $ripIdle,
                'documento' => $ripDoc,
                'dimensione' => $ripCount,
            ],
            'stampa' => [
                'documento' => $printDoc,
                'utente' => $printUser,
                'copie' => $printCopies,
                'copie_fatte' => $printCount,
                'copie_totali' => $printTotal,
                'pagine' => $pageCount,
                'progresso' => $printTotal > 0 ? round(($printCount / $printTotal) * 100) : 0,
            ],
            'online' => true,
        ];
    }

    /**
     * Status API REST (sempre disponibile)
     */
    public function getApiStatus(): ?array
    {
        try {
            $loginResponse = $this->http()->post($this->baseUrl . '/live/api/v5/login', [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if (!$loginResponse->successful()) {
                return null;
            }

            $cookies = $loginResponse->cookies();
            $statusResponse = $this->http()
                ->withCookies($cookies, $this->host)
                ->get($this->baseUrl . '/live/api/v5/status');

            if ($statusResponse->successful()) {
                $data = $statusResponse->json();
                return $data['data']['item'] ?? null;
            }
        } catch (\Exception $e) {
            // API non disponibile
        }

        return null;
    }

    /**
     * Lista job (richiede licenza API attiva)
     */
    public function getJobs(): ?array
    {
        try {
            $loginResponse = $this->http()->post($this->baseUrl . '/live/api/v5/login', [
                'username' => $this->username,
                'password' => $this->password,
                'accessrights' => $this->apiKey,
            ]);

            if (!$loginResponse->successful()) {
                return null;
            }

            $cookies = $loginResponse->cookies();
            $jobsResponse = $this->http()
                ->withCookies($cookies, $this->host)
                ->get($this->baseUrl . '/live/api/v5/jobs');

            if ($jobsResponse->successful()) {
                return $jobsResponse->json();
            }
        } catch (\Exception $e) {
            // Licenza API probabilmente scaduta
        }

        return null;
    }

    /**
     * Verifica se il Fiery è raggiungibile
     */
    public function isOnline(): bool
    {
        try {
            $response = $this->http()->timeout(5)->get($this->baseUrl . '/wt4/home/get_server_status', [
                'client_locale' => 'it_IT',
            ]);
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
