<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SolarLogService
{
    protected string $portalUrl = 'https://solarlog-portal.it';
    protected string $username;
    protected string $password;
    protected float $impiantoKwp = 180.0; // 7 inverter, totale 180 kWp

    public function __construct()
    {
        $this->username = env('SOLARLOG_USER', 'vittorio81@katamail.com');
        $this->password = env('SOLARLOG_PASS', 'nappa2017');
    }

    /**
     * Restituisce i dati energetici dell'impianto.
     * Cache 5 minuti per non stressare il portale.
     */
    public function getDati(): array
    {
        return Cache::remember('solarlog_dati', 300, function () {
            return $this->fetchDati();
        });
    }

    protected function fetchDati(): array
    {
        try {
            $jar = new CookieJar();
            $client = new Client(['verify' => false, 'cookies' => $jar, 'timeout' => 15]);

            // Login
            $client->post("{$this->portalUrl}/974821.html", [
                'form_params' => [
                    'username' => $this->username,
                    'password' => $this->password,
                    'action' => 'login',
                ],
            ]);

            // Session ID
            $sessid = '';
            foreach ($jar->toArray() as $c) {
                if ($c['Name'] === 'SDS-Portal-V2') $sessid = $c['Value'];
            }
            if (!$sessid) return $this->datiDefault('Login fallito');

            // RPC: openPlant per inverter dettaglio
            $rpcUrl = "{$this->portalUrl}/sds/rpc.php?sessid={$sessid}";
            $sOptions = base64_encode(serialize([
                'showPlantGroups' => false, 'allowExport' => false, 'showSearch' => false,
                'showStatus' => false, 'yieldMode' => 'absolute', 'showDevices' => ['6808'],
                'viewMode' => 'frontend', 'default_sorting' => null,
                'showReferenceWeather' => false, 'showReferencePlants' => false,
            ]));

            $resp = $client->post($rpcUrl, [
                'form_params' => [
                    'module' => 'sdsYieldOverview',
                    'func' => 'openPlant',
                    'yieldOverviewPlant' => 6808,
                    'sOptions' => $sOptions,
                ],
                'headers' => ['X-Requested-With' => 'XMLHttpRequest'],
            ]);

            $html = (string)$resp->getBody();
            return $this->parseInverterHtml($html);

        } catch (\Throwable $e) {
            Log::warning('SolarLog errore: ' . $e->getMessage());
            return $this->datiDefault($e->getMessage());
        }
    }

    protected function parseInverterHtml(string $html): array
    {
        $inverter = [];
        $totKwp = 0;
        $totOggi = 0;
        $totIeri = 0;
        $tot7gg = 0;
        $tot30gg = 0;
        $count = 0;

        // Parse ogni inverter (record_item ma non sum_row)
        if (preg_match_all('/<div class="record_item" name="yieldOverviewInverter">(.*?)<div class="CLEAR"><\/div>/s', $html, $matches)) {
            foreach ($matches[1] as $block) {
                // Estrai i 7 campi (col1-col7)
                if (preg_match_all('/<div class="col\d"[^>]*>\s*([^<]+)/s', $block, $cols)) {
                    $vals = array_map('trim', $cols[1]);
                    if (count($vals) >= 7) {
                        $nome = $vals[0];
                        $tipo = $vals[1];
                        $kwp = (float)str_replace(',', '.', $vals[2]);
                        $oggi = (float)str_replace(',', '.', $vals[3]);
                        $ieri = (float)str_replace(',', '.', $vals[4]);
                        $sett = (float)str_replace(',', '.', $vals[5]);
                        $mese = (float)str_replace(',', '.', $vals[6]);

                        // Controlla se è la riga "Media" (sum_row)
                        if (stripos($nome, 'Media') !== false) continue;

                        $online = ($oggi > 0 || $ieri > 0);
                        $inverter[] = [
                            'nome' => $nome,
                            'tipo' => $tipo,
                            'kwp' => $kwp,
                            'oggi_kwh_kwp' => $oggi,
                            'ieri_kwh_kwp' => $ieri,
                            'sett_kwh_kwp' => $sett,
                            'mese_kwh_kwp' => $mese,
                            'oggi_kwh' => round($kwp * $oggi, 1),
                            'online' => $online,
                        ];

                        $totKwp += $kwp;
                        $totOggi += $kwp * $oggi;
                        $totIeri += $kwp * $ieri;
                        $tot7gg += $kwp * $sett;
                        $tot30gg += $kwp * $mese;
                        $count++;
                    }
                }
            }
        }

        $onlineCount = collect($inverter)->where('online', true)->count();

        return [
            'ok' => true,
            'impianto_kwp' => round($totKwp, 1),
            'oggi_kwh' => round($totOggi, 1),
            'ieri_kwh' => round($totIeri, 1),
            'settimana_kwh' => round($tot7gg, 1),
            'mese_kwh' => round($tot30gg, 1),
            'potenza_attuale_kw' => 0, // non disponibile da questa API
            'inverter_online' => $onlineCount,
            'inverter_totali' => $count,
            'inverter' => $inverter,
            'ultimo_aggiornamento' => now()->format('H:i'),
            'errore' => null,
        ];
    }

    protected function datiDefault(string $errore = null): array
    {
        return [
            'ok' => false,
            'impianto_kwp' => 180,
            'oggi_kwh' => 0,
            'ieri_kwh' => 0,
            'settimana_kwh' => 0,
            'mese_kwh' => 0,
            'potenza_attuale_kw' => 0,
            'inverter_online' => 0,
            'inverter_totali' => 7,
            'inverter' => [],
            'ultimo_aggiornamento' => now()->format('H:i'),
            'errore' => $errore,
        ];
    }
}
