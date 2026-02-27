<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OndaSyncService;

class SyncOnda extends Command
{
    protected $signature = 'onda:sync';
    protected $description = 'Sincronizza ordini e fasi da Onda (SQL Server)';

    public function handle()
    {
        $this->info('Sync Onda in corso...');

        try {
            $risultato = OndaSyncService::sincronizza();

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

            $ddtAvviate = OndaSyncService::sincronizzaDDTFornitore();
            $this->info("Fasi esterne avviate da DDT fornitore: {$ddtAvviate}");

            $ddtVendita = OndaSyncService::sincronizzaDDTVendita();
            $this->info("Ordini aggiornati con DDT vendita: {$ddtVendita}");

            $this->info('Sync completato.');
        } catch (\Exception $e) {
            $this->error('Errore: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
