<?php

namespace App\Console\Commands;

use App\Http\Services\BrtService;
use App\Mail\ConsegnaInRitardo;
use App\Models\Ordine;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckBrtRitardi extends Command
{
    protected $signature = 'brt:check-ritardi';
    protected $description = 'Controlla DDT BRT non consegnati entro 3 giorni dal ritiro e invia email di notifica';

    public function handle(BrtService $brt)
    {
        // Cache giornaliera: evita email duplicate nello stesso giorno
        $cacheKey = 'brt_ritardi_' . now()->format('Y-m-d');
        if (Cache::has($cacheKey)) {
            $this->info('Email ritardi già inviata oggi, skip.');
            return 0;
        }

        $this->info('Controllo DDT BRT in ritardo...');

        // DDT BRT unici con vettore BRT
        $ddtGroups = Ordine::where('vettore_ddt', 'LIKE', '%BRT%')
            ->whereNotNull('numero_ddt_vendita')
            ->where('numero_ddt_vendita', '!=', '')
            ->get()
            ->groupBy('numero_ddt_vendita');

        $ritardi = [];

        foreach ($ddtGroups as $numeroDDT => $ordini) {
            $this->line("  DDT {$numeroDDT}...");

            // Chiama tracking SOAP BRT
            $tracking = $brt->getTrackingByDDT($numeroDDT);
            if (!$tracking || ($tracking['esito'] ?? -1) != 0) {
                $this->warn("    Tracking non disponibile per DDT {$numeroDDT}");
                continue;
            }

            // Se già consegnata, skip
            $stato = $tracking['stato'] ?? '';
            if (stripos($stato, 'CONSEGNATA') !== false) {
                $this->line("    Già consegnata, skip.");
                continue;
            }

            // Cerca data ritiro: data_spedizione dalla bolla, oppure primo evento "RITIRATA"
            $dataRitiro = null;

            // 1) data_spedizione dalla bolla
            $dataSped = $tracking['bolla']['data_spedizione'] ?? '';
            if (!empty($dataSped)) {
                try {
                    $dataRitiro = Carbon::parse($dataSped);
                } catch (\Exception $e) {
                    // formato non valido, proviamo dagli eventi
                }
            }

            // 2) fallback: primo evento con "RITIRATA" o primo evento in assoluto
            if (!$dataRitiro && !empty($tracking['eventi'])) {
                $eventi = $tracking['eventi'];
                // Cerca evento RITIRATA
                foreach ($eventi as $ev) {
                    if (stripos($ev['descrizione'] ?? '', 'RITIRATA') !== false && !empty($ev['data'])) {
                        try {
                            $dataRitiro = Carbon::parse($ev['data']);
                            break;
                        } catch (\Exception $e) {}
                    }
                }
                // Se non trovato, prendi l'ultimo evento (il più vecchio, in fondo alla lista)
                if (!$dataRitiro) {
                    $ultimoEvento = end($eventi);
                    if (!empty($ultimoEvento['data'])) {
                        try {
                            $dataRitiro = Carbon::parse($ultimoEvento['data']);
                        } catch (\Exception $e) {}
                    }
                }
            }

            if (!$dataRitiro) {
                $this->warn("    Nessuna data ritiro trovata per DDT {$numeroDDT}");
                continue;
            }

            // Controlla se sono passati 3+ giorni dal ritiro
            $giorniDalRitiro = $dataRitiro->diffInDays(now());
            if ($giorniDalRitiro < 3) {
                $this->line("    Ritirata {$giorniDalRitiro}gg fa, ancora nei tempi.");
                continue;
            }

            $this->warn("    IN RITARDO! Ritirata {$giorniDalRitiro}gg fa, stato: {$stato}");

            $primoOrdine = $ordini->first();
            $ritardi[] = [
                'numero_ddt' => $numeroDDT,
                'cliente' => $primoOrdine->cliente_nome ?? '-',
                'commesse' => $ordini->pluck('commessa')->filter()->unique()->implode(', '),
                'data_ritiro' => $dataRitiro->format('d/m/Y'),
                'giorni_ritardo' => $giorniDalRitiro,
                'stato_brt' => $stato,
                'destinatario' => $tracking['bolla']['destinatario_ragione_sociale'] ?? '-',
                'localita' => trim(($tracking['bolla']['destinatario_localita'] ?? '') . ' ' . ($tracking['bolla']['destinatario_provincia'] ?? '')),
                'colli' => $tracking['bolla']['colli'] ?? '-',
            ];
        }

        if (empty($ritardi)) {
            $this->info('Nessun DDT BRT in ritardo.');
            return 0;
        }

        $this->info(count($ritardi) . ' DDT in ritardo, invio email...');

        Mail::to(['anappa@graficanappa.com', 'logistica@graficanappa.com'])
            ->send(new ConsegnaInRitardo($ritardi));

        // Segna come inviato per oggi
        Cache::put($cacheKey, true, now()->endOfDay());

        $this->info('Email inviata.');
        Log::info('BRT ritardi: email inviata', ['count' => count($ritardi)]);

        return 0;
    }
}
