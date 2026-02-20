<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Services\ExcelSyncService;

class ExcelSync extends Command
{
    protected $signature = 'excel:sync';
    protected $description = 'Sincronizza bidirezionalmente Excel e database MES';

    public function handle()
    {
        $this->info('Excel sync in corso...');

        try {
            // 1. Importa eventuali modifiche dal file Excel
            ExcelSyncService::syncIfModified();
            $this->info('Import completato.');

            // 2. Esporta dati aggiornati nel file Excel
            ExcelSyncService::exportToExcel();
            $this->info('Export completato.');

            $this->info('Excel sync terminato.');
        } catch (\Exception $e) {
            $this->error('Errore: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
