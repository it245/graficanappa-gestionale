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
        // Sync può essere lungo (~3-5 min per 9000+ righe). Disabilita timeout PHP
        // SIA via set_time_limit (per cli con safe_mode off) SIA via ini_set.
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '1G');

        try {
            @set_time_limit(0);
            ExcelSyncService::syncIfModified();
            @set_time_limit(0);
            ExcelSyncService::exportToExcel();
        } catch (\Exception $e) {
            $this->error('Errore: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
