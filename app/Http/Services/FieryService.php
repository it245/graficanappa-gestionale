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
        if ($deviceState === 'STR_ERROR' || $deviceState === 'ERROR') {
            $stato = 'errore';
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
     * Login API v5 e ritorna array di cookie.
     * Cachea i cookie per 10 minuti per evitare login ripetuti.
     */
    private function apiLogin(): ?array
    {
        return Cache::remember('fiery_api_cookies', 600, function () {
            try {
                $loginResponse = $this->http()->post($this->baseUrl . '/live/api/v5/login', [
                    'username' => $this->username,
                    'password' => $this->password,
                    'accessrights' => $this->apiKey,
                ]);

                if (!$loginResponse->successful()) {
                    return null;
                }

                $cookieJar = $loginResponse->cookies();
                $cookies = [];
                foreach ($cookieJar as $cookie) {
                    $cookies[$cookie->getName()] = $cookie->getValue();
                }
                return $cookies;
            } catch (\Exception $e) {
                return null;
            }
        });
    }

    /**
     * Chiamata autenticata API v5
     */
    private function apiGet(string $endpoint, array $query = [])
    {
        $cookies = $this->apiLogin();
        if (!$cookies) return null;

        try {
            $response = $this->http()
                ->timeout(30)
                ->withCookies($cookies, $this->host)
                ->get($this->baseUrl . $endpoint, $query);

            if ($response->status() === 401) {
                // Cookie scaduti, riprova con login fresco
                Cache::forget('fiery_api_cookies');
                $cookies = $this->apiLogin();
                if (!$cookies) return null;

                $response = $this->http()
                    ->timeout(30)
                    ->withCookies($cookies, $this->host)
                    ->get($this->baseUrl . $endpoint, $query);
            }

            return $response->successful() ? $response->json() : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Lista completa job dal API v5 con dati strutturati.
     * Ritorna array di job con campi normalizzati.
     */
    public function getJobs(): ?array
    {
        $json = $this->apiGet('/live/api/v5/jobs');
        if (!$json) return null;

        $items = $json['data']['items'] ?? [];

        return collect($items)->map(function ($job) {
            return [
                'id' => $job['id'] ?? null,
                'title' => $job['title'] ?? '',
                'state' => $job['state'] ?? '',
                'status' => $job['status'] ?? '',
                'date' => $job['date'] ?? '',
                'username' => $job['username'] ?? '',
                'copies_printed' => (int) ($job['copies printed'] ?? 0),
                'num_copies' => (int) ($job['num copies'] ?? 0),
                'total_sheets' => (int) ($job['total sheets printed'] ?? 0),
                'total_pages' => (int) ($job['total pages printed'] ?? 0),
                'total_color' => (int) ($job['total color pages printed'] ?? 0),
                'total_bw' => (int) ($job['total bw pages printed'] ?? 0),
                'num_pages' => (int) ($job['num pages'] ?? 0),
                'media_size' => $job['media size'] ?? '',
                'media_weight' => $job['media weight'] ?? '',
                'duplex' => ($job['EFDuplex'] ?? 'False') !== 'False',
                'input_slot' => $job['input slot'] ?? '',
                'commessa' => $this->estraiCommessaDaTitolo($job['title'] ?? ''),
            ];
        })->toArray();
    }

    /**
     * Estrae il codice commessa dal titolo del job Fiery.
     * "66590_PieghevoliVari_33x48.pdf" → "0066590-26"
     */
    public function estraiCommessaDaTitolo(string $title): ?string
    {
        if (preg_match('/^(\d{4,6})_/', $title, $m)) {
            return '00' . $m[1] . '-26';
        }
        return null;
    }

    /**
     * Fogli totali per commessa dall'Accounting API (tutti i run storici).
     * Cachea i dati per 5 minuti per evitare chiamate ripetute.
     * Ritorna array ['commessa' => ['fogli' => X, 'copie' => Y, 'run' => Z, 'run_dettaglio' => [...]]]
     */
    public function getAccountingPerCommessa(): ?array
    {
        return Cache::remember('fiery_accounting_commesse', 300, function () {
            $json = $this->apiGet('/live/api/v5/accounting');
            if (!$json) return null;

            $items = $json['data']['items'] ?? $json;
            if (!is_array($items)) return null;

            $perCommessa = [];
            foreach ($items as $entry) {
                $title = $entry['title'] ?? '';
                $commessa = $this->estraiCommessaDaTitolo($title);
                if (!$commessa) continue;

                $fogli = (int) ($entry['total sheets printed'] ?? 0);
                $copie = (int) ($entry['copies printed'] ?? 0);
                $printStatus = $entry['print status'] ?? '';

                if (!isset($perCommessa[$commessa])) {
                    $perCommessa[$commessa] = [
                        'fogli' => 0,
                        'copie' => 0,
                        'run' => 0,
                        'run_dettaglio' => [],
                    ];
                }

                $perCommessa[$commessa]['fogli'] += $fogli;
                $perCommessa[$commessa]['copie'] += $copie;
                $perCommessa[$commessa]['run']++;
                $perCommessa[$commessa]['run_dettaglio'][] = [
                    'title' => $title,
                    'copie' => $copie,
                    'fogli' => $fogli,
                    'status' => $printStatus,
                ];
            }

            return $perCommessa;
        });
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
