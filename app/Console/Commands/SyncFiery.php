<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Services\FieryService;
use App\Http\Services\FierySyncService;

class SyncFiery extends Command
{
    protected $signature = 'fiery:sync';
    protected $description = 'Sincronizza stato Fiery con le fasi digitali del MES';

    public function handle(FieryService $fiery, FierySyncService $syncService)
    {
        try {
            $risultato = $syncService->sincronizza();

            if (!$risultato) {
                $this->line('Fiery: nessuna azione (idle o commessa non trovata)');
                return 0;
            }

            $this->info("Fiery sync: commessa {$risultato['commessa']} - {$risultato['fase_avviata']} avviata ({$risultato['operatore']})");
        } catch (\Exception $e) {
            $this->error('Errore Fiery sync: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
