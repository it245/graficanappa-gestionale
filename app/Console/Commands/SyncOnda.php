<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OndaSyncService;
use App\Modules\Onda\Services\OrdineSyncService;
use App\Modules\Onda\Services\CommessaSyncService;
use App\Modules\Spedizione\Services\DdtSyncService;

class SyncOnda extends Command
{
    protected $signature = 'onda:sync {commessa? : Codice commessa singola da sincronizzare (senza filtro data)}';
    protected $description = 'Sincronizza ordini e fasi da Onda (SQL Server). Con argomento: sync singola commessa.';

    public function handle()
    {
        $commessa = $this->argument('commessa');

        if ($commessa) {
            return $this->syncSingolaCommessa($commessa);
        }

        return $this->syncTutte();
    }

    protected function syncSingolaCommessa(string $commessa): int
    {
        $this->info("Sync commessa $commessa da Onda (senza filtro data)...");

        try {
            // Strangler Fig: chiama direttamente il modulo Onda invece del wrapper legacy.
            $risultato = app(CommessaSyncService::class)->sync($commessa);

            if (!$risultato['trovata']) {
                $this->warn($risultato['messaggio']);
                return 1;
            }

            $this->info($risultato['messaggio']);
            if (!empty($risultato['fasi'])) {
                foreach ($risultato['fasi'] as $fase) {
                    $this->line("  + $fase");
                }
            }

            $this->info('Sync commessa completato.');
        } catch (\Exception $e) {
            $this->error('Errore: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    protected function syncTutte(): int
    {
        $this->info('Sync Onda in corso...');

        try {
            // Strangler Fig: chiama direttamente OrdineSyncService dal modulo Onda.
            // Mantiene la stessa shape di output del wrapper legacy.
            $r = app(OrdineSyncService::class)->sync();
            $risultato = [
                'ordini_creati'         => $r['ordini_creati'] ?? 0,
                'ordini_aggiornati'     => $r['ordini_aggiornati'] ?? 0,
                'fasi_create'           => $r['fasi_create'] ?? 0,
                'log_ordini_creati'     => $r['log_ordini_creati'] ?? [],
                'log_ordini_aggiornati' => $r['log_ordini_aggiornati'] ?? [],
                'log_fasi_create'       => $r['log_fasi_create'] ?? [],
            ];

            $this->info("Ordini creati: {$risultato['ordini_creati']}");
            if (!empty($risultato['log_ordini_creati'])) {
                foreach ($risultato['log_ordini_creati'] as $log) {
                    $this->line("  + {$log}");
                }
            }

            $this->info("Ordini aggiornati: {$risultato['ordini_aggiornati']}");
            if (!empty($risultato['log_ordini_aggiornati'])) {
                $commesse = array_unique($risultato['log_ordini_aggiornati']);
                $this->line("  " . implode(', ', $commesse));
            }

            $this->info("Fasi create: {$risultato['fasi_create']}");
            if (!empty($risultato['log_fasi_create'])) {
                foreach ($risultato['log_fasi_create'] as $log) {
                    $this->line("  + {$log}");
                }
            }

            // I metodi DDT Fornitore / Forniture Lavorazioni non sono ancora migrati
            // in moduli dedicati: continuano a passare dal wrapper legacy
            // (estrazione futura: DdtFornitoreSyncService, DdtLavorazioniSyncService).
            $ddtAvviate = OndaSyncService::sincronizzaDDTFornitore();
            $this->info("Fasi esterne avviate da DDT fornitore: {$ddtAvviate}");

            $ddtLavorazioni = OndaSyncService::sincronizzaDDTFornitureLavorazioni();
            $this->info("Fasi esterne da descrizione DDT: {$ddtLavorazioni}");

            // DDT vendita: già migrato in Spedizione\DdtSyncService.
            $venditaR = app(DdtSyncService::class)->syncFromOnda(7);
            $ddtVendita = ($venditaR['inseriti'] ?? 0) + ($venditaR['aggiornati'] ?? 0);
            $this->info("Ordini aggiornati con DDT vendita: {$ddtVendita}");

            $this->info('Sync completato.');
        } catch (\Exception $e) {
            $this->error('Errore: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
