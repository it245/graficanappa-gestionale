<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SoapClient;

class BrtService
{
    protected $baseUrl;
    protected $userID;
    protected $password;

    public function __construct()
    {
        $this->baseUrl = config('services.brt.base_url');
        $this->userID = config('services.brt.user_id');
        $this->password = config('services.brt.password');
    }

    /**
     * Tracking via REST API (per segnacollo/parcelID)
     */
    public function getTracking(string $parcelID): ?array
    {
        try {
            $response = Http::withHeaders([
                'userID' => $this->userID,
                'password' => $this->password,
            ])->withOptions([
                'verify' => false,
            ])->timeout(15)->get("{$this->baseUrl}/tracking/parcelID/{$parcelID}");

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'error' => true,
                'status' => $response->status(),
                'message' => 'Errore API BRT: ' . $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Errore connessione BRT: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Crea un SoapClient BRT con fix http→https nel WSDL
     */
    protected function getSoapClient(string $wsdlUrl): SoapClient
    {
        $ctx = stream_context_create([
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
            'http' => ['timeout' => 30],
        ]);
        $wsdl = file_get_contents($wsdlUrl, false, $ctx);
        $wsdl = str_replace('http://wsr.brt.it', 'https://wsr.brt.it', $wsdl);
        $tmp = tempnam(sys_get_temp_dir(), 'brt_') . '.xml';
        file_put_contents($tmp, $wsdl);

        return new SoapClient($tmp, [
            'trace' => true,
            'exceptions' => true,
            'connection_timeout' => 30,
            'stream_context' => $ctx,
            'cache_wsdl' => WSDL_CACHE_NONE,
        ]);
    }

    /**
     * Trova l'ID spedizione BRT dal numero DDT.
     * Prova diverse strategie di ricerca:
     * 1. RIFERIMENTO_MITTENTE_ALFABETICO = DDT (es. "466")
     * 2. RIFERIMENTO_MITTENTE_NUMERICO = DDT
     * 3. Entrambi i campi
     */
    public function getSpedizioneIdByDDT(string $numeroDDT): ?string
    {
        try {
            $rma = ltrim($numeroDDT, '0');
            if (empty($rma)) return null;

            $soap = $this->getSoapClient(
                'https://wsr.brt.it:10052/web/GetIdSpedizioneByRMAService/GetIdSpedizioneByRMA?wsdl'
            );

            // Strategia 1: cerca per RIFERIMENTO_MITTENTE_ALFABETICO
            $id = $this->cercaSpedizione($soap, ['RIFERIMENTO_MITTENTE_ALFABETICO' => $rma]);
            if ($id) return $id;

            // Strategia 2: cerca per RIFERIMENTO_MITTENTE_NUMERICO
            $id = $this->cercaSpedizione($soap, ['RIFERIMENTO_MITTENTE_NUMERICO' => $rma]);
            if ($id) return $id;

            // Strategia 3: cerca con entrambi
            $id = $this->cercaSpedizione($soap, [
                'RIFERIMENTO_MITTENTE_ALFABETICO' => $rma,
                'RIFERIMENTO_MITTENTE_NUMERICO' => $rma,
            ]);

            return $id;
        } catch (\Exception $e) {
            Log::warning('BRT SOAP GetIdSpedizioneByRMA errore', [
                'ddt' => $numeroDDT,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Esegue la ricerca SOAP con i parametri dati.
     */
    protected function cercaSpedizione(SoapClient $soap, array $params): ?string
    {
        try {
            $args = array_merge(['CLIENTE_ID' => $this->userID], $params);

            $result = $soap->getidspedizionebyrma(['arg0' => $args]);

            $esito = $result->return->ESITO ?? -1;
            $spedizioneId = $result->return->SPEDIZIONE_ID ?? null;

            if ($esito == 0 && $spedizioneId) {
                return (string) $spedizioneId;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Tracking completo via SOAP (per ID spedizione BRT).
     * Restituisce un array normalizzato con bolla, eventi, ecc.
     */
    public function getTrackingBySpedizioneId(string $spedizioneId): ?array
    {
        try {
            $soap = $this->getSoapClient(
                'https://wsr.brt.it:10052/web/BRT_TrackingByBRTshipmentIDService/BRT_TrackingByBRTshipmentID?wsdl'
            );

            $result = $soap->brt_trackingbybrtshipmentid([
                'arg0' => [
                    'LINGUA_ISO639_ALPHA2' => '',
                    'SPEDIZIONE_ANNO' => 0,
                    'SPEDIZIONE_BRT_ID' => $spedizioneId,
                ]
            ]);

            $ret = $result->return ?? null;
            if (!$ret || ($ret->ESITO ?? -1) != 0) {
                return null;
            }

            $bolla = $ret->BOLLA ?? null;
            $data = [
                'esito' => $ret->ESITO,
                'spedizione_id' => $spedizioneId,
            ];

            if ($bolla) {
                $sped = $bolla->DATI_SPEDIZIONE ?? null;
                $cons = $bolla->DATI_CONSEGNA ?? null;
                $rif = $bolla->RIFERIMENTI ?? null;
                $dest = $bolla->DESTINATARIO ?? null;
                $mitt = $bolla->MITTENTE ?? null;
                $merce = $bolla->MERCE ?? null;

                $data['bolla'] = [
                    'spedizione_id' => $sped->SPEDIZIONE_ID ?? '',
                    'data_spedizione' => $sped->SPEDIZIONE_DATA ?? '',
                    'servizio' => $sped->SERVIZIO ?? '',
                    'porto' => $sped->PORTO ?? '',
                    'filiale_arrivo' => $sped->FILIALE_ARRIVO ?? '',
                    'data_consegna' => $cons->DATA_CONSEGNA_MERCE ?? '',
                    'ora_consegna' => $cons->ORA_CONSEGNA_MERCE ?? '',
                    'firmatario' => $cons->FIRMATARIO_CONSEGNA ?? '',
                    'rif_mittente_alfa' => $rif->RIFERIMENTO_MITTENTE_ALFABETICO ?? '',
                    'rif_mittente_num' => $rif->RIFERIMENTO_MITTENTE_NUMERICO ?? '',
                    'destinatario_localita' => $dest->LOCALITA ?? '',
                    'destinatario_provincia' => $dest->SIGLA_PROVINCIA ?? '',
                    'destinatario_ragione_sociale' => $dest->RAGIONE_SOCIALE ?? '',
                    'mittente_localita' => $mitt->LOCALITA ?? '',
                    'colli' => $merce->COLLI ?? 0,
                    'peso_kg' => $merce->PESO_KG ?? 0,
                ];
            }

            // Eventi (filtriamo quelli vuoti)
            $eventi = [];
            if (isset($ret->LISTA_EVENTI)) {
                $listaEventi = is_array($ret->LISTA_EVENTI) ? $ret->LISTA_EVENTI : [$ret->LISTA_EVENTI];
                foreach ($listaEventi as $ev) {
                    $evento = $ev->EVENTO ?? $ev;
                    if (!empty($evento->DESCRIZIONE)) {
                        $eventi[] = [
                            'data' => $evento->DATA ?? '',
                            'ora' => $evento->ORA ?? '',
                            'descrizione' => $evento->DESCRIZIONE ?? '',
                            'filiale' => $evento->FILIALE ?? '',
                            'id' => $evento->ID ?? '',
                        ];
                    }
                }
            }
            $data['eventi'] = $eventi;

            // Stato dalla descrizione dell'ultimo evento
            $data['stato'] = !empty($eventi) ? $eventi[0]['descrizione'] : 'SCONOSCIUTO';

            return $data;
        } catch (\Exception $e) {
            Log::warning('BRT SOAP Tracking errore', [
                'spedizione_id' => $spedizioneId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Tracking completo partendo dal numero DDT.
     * DDT Onda (es. "0000437") → strip zeri → SOAP GetIdSpedizioneByRMA → SOAP Tracking
     */
    public function getTrackingByDDT(string $numeroDDT): ?array
    {
        $spedizioneId = $this->getSpedizioneIdByDDT($numeroDDT);
        if (!$spedizioneId) {
            return null;
        }

        return $this->getTrackingBySpedizioneId($spedizioneId);
    }
}
