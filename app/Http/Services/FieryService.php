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
    public function getServerStatus(bool $fast = true): ?array
    {
        // Check cache (salvata solo se successo, mai null)
        $cached = Cache::get('fiery_server_status');
        if (!empty($cached)) return $cached;

        // Fast mode (web): 1 try 2s. Slow mode (background warm): 3 retry 10s
        $maxAttempts = $fast ? 1 : 3;
        $reqTimeout = $fast ? 2 : 10;
        $connTimeout = $fast ? 1 : 4;
        $response = null;
        $lastErr = null;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($attempt > 1) usleep(500000);
            try {
                $response = Http::withoutVerifying()
                    ->withOptions(['verify' => false, 'http_errors' => false])
                    ->timeout($reqTimeout)
                    ->connectTimeout($connTimeout)
                    ->get($this->baseUrl . '/live/api/v5/server/status');
                if ($response->successful()) break;
                $lastErr = 'HTTP ' . $response->status();
                $response = null;
            } catch (\Exception $e) {
                $lastErr = $e->getMessage();
                $response = null;
            }
        }

        if (!$response) {
            \Log::warning('Fiery getServerStatus fallito: ' . $lastErr);
            // Fallback stale: ultimo stato noto fino 30 min
            $stale = Cache::get('fiery_server_status_stale');
            if (!empty($stale)) {
                $stale['avviso'] = 'dati cachati - connessione lenta';
                $stale['stale'] = true;
                return $stale;
            }
            return null;
        }

        try {

            $data = $response->json('data.item', []);
            if (empty($data)) return null;

            $fiery = strtolower($data['fiery'] ?? '');
            $ext = strtolower($data['fieryExtendedStatus'] ?? 'none');

            // Default: idle se raggiungibile, offline solo se /server/status risponde ma vuoto
            $stato = 'idle';
            if (str_contains($fiery, 'print')) $stato = 'stampa';

            // Job in stampa derivato da getJobs cache (evita chiamata API redundante /jobs/printing)
            $stampaDoc = null;
            $stampaCopieFatte = 0;
            $stampaCopieTotali = 0;
            $stampaUser = '-';
            $jobsCached = Cache::get('fiery_jobs_parsed') ?: Cache::get('fiery_jobs_parsed_stale') ?: [];
            foreach ($jobsCached as $j) {
                if (($j['state'] ?? '') === 'printing') {
                    $stato = 'stampa';
                    $stampaDoc = $j['title'] ?? null;
                    $stampaCopieFatte = (int) ($j['copies_printed'] ?? 0);
                    $stampaCopieTotali = (int) ($j['num_copies'] ?? 0);
                    $stampaUser = $j['username'] ?? '-';
                    break;
                }
            }

            $progresso = $stampaCopieTotali > 0
                ? round(($stampaCopieFatte / $stampaCopieTotali) * 100)
                : 0;

            $result = [
                'stato' => $stato,
                'online' => true,
                'stampa' => [
                    'documento' => $stampaDoc,
                    'pagine' => 0,
                    'copie_fatte' => $stampaCopieFatte,
                    'copie_totali' => $stampaCopieTotali,
                    'progresso' => $progresso,
                    'utente' => $stampaUser,
                ],
                'rip' => ['idle' => true, 'documento' => null, 'count' => 0],
                'avviso' => $ext !== 'none' ? $ext : null,
                'ultimo_aggiornamento' => now()->format('H:i:s'),
                'raw' => $data,
            ];

            // Cache fresca 30s, stale 30 min per fallback durature
            Cache::put('fiery_server_status', $result, 30);
            Cache::put('fiery_server_status_stale', $result, 1800);
            return $result;
        } catch (\Exception $e) {
            return null;
        }
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
    private function apiGet(string $endpoint, array $query = [], bool $fast = true)
    {
        $cookies = $this->apiLogin();
        if (!$cookies) return null;

        // Endpoint pesante (accounting con storico) richiede timeout più lungo in slow mode
        $isHeavyEndpoint = str_contains($endpoint, '/accounting');

        // Fast (web): 1 try 2s. Slow (background): 2 retry 10s, accounting 3 retry 30s
        $maxAttempts = $fast ? 1 : ($isHeavyEndpoint ? 3 : 2);
        $reqTimeout = $fast ? 2 : ($isHeavyEndpoint ? 30 : 10);
        $connTimeout = $fast ? 1 : 4;
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $response = $this->http()
                    ->timeout($reqTimeout)
                    ->connectTimeout($connTimeout)
                    ->withCookies($cookies, $this->host)
                    ->get($this->baseUrl . $endpoint, $query);

                if ($response->status() === 401) {
                    Cache::forget('fiery_api_cookies');
                    $cookies = $this->apiLogin();
                    if (!$cookies) return null;
                    $response = $this->http()
                        ->timeout($reqTimeout)
                        ->connectTimeout($connTimeout)
                        ->withCookies($cookies, $this->host)
                        ->get($this->baseUrl . $endpoint, $query);
                }

                if ($response->successful()) return $response->json();
            } catch (\Exception $e) {
                if ($attempt === $maxAttempts) break;
                usleep(500000);
            }
        }
        return null;
    }

    /**
     * Lista completa job dal API v5 con dati strutturati.
     * Ritorna array di job con campi normalizzati.
     */
    public function getJobs(bool $fast = true): ?array
    {
        // Cache fresca (60s) se non vuoto
        $cached = Cache::get('fiery_jobs_parsed');
        if (!empty($cached)) return $cached;

        $json = $this->apiGet('/live/api/v5/jobs', [], $fast);
        if (!$json) {
            // Fallback stale (30 min) per evitare flicker dashboard
            $stale = Cache::get('fiery_jobs_parsed_stale');
            return !empty($stale) ? $stale : null;
        }

        $items = $json['data']['items'] ?? [];
        $parsed = collect($items)->map(function ($job) {
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
                    'timings' => $this->getJobTimings($job),
                ];
            })->toArray();

        if (!empty($parsed)) {
            Cache::put('fiery_jobs_parsed', $parsed, 60);
            Cache::put('fiery_jobs_parsed_stale', $parsed, 1800);
        }
        return $parsed;
    }

    /**
     * Estrae il codice commessa dal titolo del job Fiery.
     * "66590_PieghevoliVari_33x48.pdf" → "0066590-26"
     */
    public function estraiCommessaDaTitolo(string $title): ?string
    {
        if (preg_match('/^(\d{4,6})[\s_]/', $title, $m)) {
            return '00' . $m[1] . '-26';
        }
        return null;
    }

    /**
     * Fogli totali per commessa dall'Accounting API (tutti i run storici).
     * Cachea i dati per 5 minuti per evitare chiamate ripetute.
     * Ritorna array ['commessa' => ['fogli' => X, 'copie' => Y, 'run' => Z, 'run_dettaglio' => [...]]]
     */
    public function getAccountingPerCommessa(bool $fast = true): ?array
    {
        $cached = Cache::get('fiery_accounting_commesse');
        if (!empty($cached)) return $cached;

        $json = $this->apiGet('/live/api/v5/accounting', [], $fast);
        if (!$json) {
            return Cache::get('fiery_accounting_commesse_stale') ?: null;
        }

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

        if (!empty($perCommessa)) {
            Cache::put('fiery_accounting_commesse', $perCommessa, 300);
            Cache::put('fiery_accounting_commesse_stale', $perCommessa, 1800);
        }
        return $perCommessa;
    }

    /**
     * Dump raw consumables JSON per debug struttura
     */
    public function getConsumablesRaw()
    {
        try {
            $http = $this->loginAndGetHttp();
            if (!$http) return ['err' => 'login failed'];
            $r = $http->get($this->baseUrl . '/live/api/v5/consumables');
            return ['status' => $r->status(), 'body' => $r->json() ?? $r->body()];
        } catch (\Exception $e) {
            return ['err' => $e->getMessage()];
        }
    }

    /**
     * Livelli consumabili (toner CMYK, waste, ADF kit, staples).
     * Cache 60s — non stressare API Fiery.
     */
    public function getConsumables(): ?array
    {
        return Cache::remember('fiery_consumables', 60, function () {
            try {
                $http = $this->loginAndGetHttp();
                if (!$http) return null;
                $r = $http->get($this->baseUrl . '/live/api/v5/consumables');
                if (!$r->successful()) return null;

                $item = $r->json('data.item', []);

                // Toner/colorants
                $toners = [];
                foreach (($item['colorants'] ?? []) as $c) {
                    $toners[] = [
                        'name' => $c['i18n'] ?? $c['name'] ?? '-',
                        'type' => 'toner',
                        'color_hex' => $c['color'] ?? '#999',
                        'level' => (int) ($c['level'] ?? 0),
                        'unit' => '%',
                    ];
                }

                // Vassoi carta
                $trays = [];
                foreach (($item['trays'] ?? []) as $t) {
                    $dim = $t['dimensions'] ?? [];
                    $trays[] = [
                        'name' => $t['i18n'] ?? $t['name'] ?? '-',
                        'trayid' => $t['trayid'] ?? null,
                        'level' => (int) ($t['level'] ?? 0),
                        'media_type' => $t['attributes']['EFMediaType'] ?? '-',
                        'page_size' => $t['attributes']['EFPrintSize'] ?? ($t['attributes']['PageSize'] ?? '-'),
                        'dimensions_mm' => !empty($dim) ? round($dim[0] / 2.834) . 'x' . round($dim[1] / 2.834) : '-',
                    ];
                }

                return ['toners' => $toners, 'trays' => $trays];
            } catch (\Exception $e) {
                return null;
            }
        });
    }

    /**
     * Info estesa server Fiery (capabilities, modello, seriale).
     * Cache 1h — dati statici.
     */
    public function getInfoExtended(): ?array
    {
        return Cache::remember('fiery_info_extended', 3600, function () {
            try {
                $http = $this->loginAndGetHttp();
                if (!$http) return null;
                // v1 ha info più ricca (4612 bytes vs 530 v5)
                $r = $http->get($this->baseUrl . '/live/api/v1/info');
                if (!$r->successful()) {
                    $r = $http->get($this->baseUrl . '/live/api/v5/info');
                }
                return $r->successful() ? $r->json('data.item', []) : null;
            } catch (\Exception $e) {
                return null;
            }
        });
    }

    /**
     * Versione firmware Fiery.
     * Cache 24h.
     */
    public function getVersion(): ?string
    {
        return Cache::remember('fiery_version', 86400, function () {
            try {
                $http = $this->loginAndGetHttp();
                if (!$http) return null;
                $r = $http->get($this->baseUrl . '/live/api/v3/version');
                if ($r->successful()) {
                    $data = $r->json('data.item', []);
                    return $data['version'] ?? $data['buildNumber'] ?? json_encode($data);
                }
                return null;
            } catch (\Exception $e) {
                return null;
            }
        });
    }

    /**
     * Estrae timing spooling/ripping/printing da un job (campi timestamp).
     */
    public function getJobTimings(array $job): array
    {
        // Parse timestamp robusto: skipa valori vuoti/strani, cap a 24h per sanity
        $ts = function ($key) use ($job) {
            $v = $job[$key] ?? null;
            if (!$v || $v === '0' || $v === 0) return null;
            $t = is_numeric($v) ? (int) $v : strtotime($v);
            // Timestamp valido solo se > 1 gen 2020 (prima = artefatto)
            return ($t && $t > 1577836800) ? $t : null;
        };
        $SANITY_MAX = 24 * 3600; // 24h max realistico per uno step
        $diff = fn($a, $b) => ($a && $b && $b >= $a) ? min($b - $a, $SANITY_MAX * 10) : null;

        $spoolStart = $ts('timestamp spooling');
        $spoolDone = $ts('timestamp done spooling');
        $ripStart = $ts('timestamp ripping');
        $ripDone = $ts('timestamp done ripping');
        $printStart = $ts('timestamp printing');
        $printDone = $ts('timestamp done printing');

        // Applica sanity: se diff > 24h per un singolo step → null (valore non attendibile)
        $sanity = fn($s) => ($s !== null && $s <= $SANITY_MAX) ? $s : null;

        return [
            'spool_sec' => $sanity($diff($spoolStart, $spoolDone)),
            'rip_sec'   => $sanity($diff($ripStart, $ripDone)),
            'print_sec' => $sanity($diff($printStart, $printDone)),
            'total_sec' => $sanity($diff($spoolStart, $printDone)),
        ];
    }

    /**
     * Login + ritorna PendingRequest con cookie sessione.
     * Helper interno per le chiamate API v5/v1/v3 protette.
     */
    private function loginAndGetHttp()
    {
        // Timeout corto per non esaurire PHP 60s budget complessivo
        $login = Http::withoutVerifying()
            ->withOptions(['verify' => false])
            ->timeout(5)
            ->post($this->baseUrl . '/live/api/v5/login', [
                'username' => $this->username,
                'password' => $this->password,
                'accessrights' => $this->apiKey,
            ]);
        if (!$login->successful()) return null;

        $cookieHeader = '';
        foreach ((array) ($login->headers()['Set-Cookie'] ?? []) as $sc) {
            if (preg_match('/^([^=]+)=([^;]+)/', $sc, $m)) {
                $cookieHeader .= $m[1] . '=' . $m[2] . '; ';
            }
        }
        return Http::withoutVerifying()
            ->withOptions(['verify' => false])
            ->timeout(5)
            ->withHeaders(['Cookie' => trim($cookieHeader, '; ')]);
    }

    /**
     * Verifica se il Fiery è raggiungibile
     */
    public function isOnline(): bool
    {
        // Prima prova WebTools (legacy)
        try {
            $response = $this->http()->timeout(3)->get($this->baseUrl . '/wt4/home/get_server_status', [
                'client_locale' => 'it_IT',
            ]);
            if ($response->successful()) return true;
        } catch (\Exception $e) {}

        // Fallback: prova API v5 login (accetta anche 401 = server raggiungibile)
        try {
            $response = $this->http()->timeout(5)->post($this->baseUrl . '/live/api/v5/login', [
                'username' => config('fiery.username'),
                'password' => config('fiery.password'),
                'accessrights' => config('fiery.api_key'),
            ]);
            return $response->status() < 500;
        } catch (\Exception $e) {
            return false;
        }
    }
}
