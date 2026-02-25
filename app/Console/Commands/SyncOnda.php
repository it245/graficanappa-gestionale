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
            $this->info("Ordini aggiornati: {$risultato['ordini_aggiornati']}");
            $this->info("Fasi create: {$risultato['fasi_create']}");

            $ddtAvviate = OndaSyncService::sincronizzaDDTFornitore();
            $this->info("Fasi esterne avviate da DDT fornitore: {$ddtAvviate}");

            $this->info('Sync completato.');
        } catch (\Exception $e) {
            $this->error('Errore: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
