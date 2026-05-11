<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Services\ExcelSyncService as ExcelSyncLegacy;
use App\Modules\Documenti\Services\ExcelSyncService as ExcelSyncModule;

class ExcelSync extends Command
{
    protected $signature = 'excel:sync';
    protected $description = 'Sincronizza bidirezionalmente Excel e database MES';

    public function handle(ExcelSyncModule $excelModule)
    {
        // Export xlsx via ZipStream alloca chunk 16MB. Con dataset ~6000 ordini
        // + fasi il default 512M si esaurisce. Bump a 1G per il singolo run.
        ini_set('memory_limit', '1G');

        $this->info('Excel sync in corso...');

        try {
            // Strangler Fig: il modulo `Documenti\ExcelSyncService` espone
            // `watchFile()` per rilevare modifiche dashboard_mes.xlsx via cache
            // (mtime persistito). E' un check informativo: il sync vero e
            // proprio resta nel legacy che fa hash-based diff piu' robusto.
            if ($excelModule->watchFile()) {
                $this->info('[Modulo] dashboard_mes.xlsx modificato dall\'ultima sync.');
            }

            // 1. Importa eventuali modifiche dal file Excel
            ExcelSyncLegacy::syncIfModified();
            $this->info('Import completato.');

            // 2. Esporta dati aggiornati nel file Excel
            ExcelSyncLegacy::exportToExcel();
            $this->info('Export completato.');

            $this->info('Excel sync terminato.');
        } catch (\Exception $e) {
            $this->error('Errore: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
