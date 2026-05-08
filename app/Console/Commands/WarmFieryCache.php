<?php

namespace App\Console\Commands;

use App\Modules\Stampa\Adapters\FieryAdapter;
use Illuminate\Console\Command;

class WarmFieryCache extends Command
{
    protected $signature = 'fiery:warm';
    protected $description = 'Popola cache Fiery in background con timeout lunghi (run via cron/Task Scheduler)';

    public function handle(FieryAdapter $fiery)
    {
        $start = microtime(true);
        $this->info('Fiery warm cache start...');

        // Strangler Fig: usa i metodi Fiery-specifici esposti dal FieryAdapter
        // (fieryServerStatus / fieryJobs / fieryAccountingPerCommessa) anziché
        // iniettare direttamente FieryService.
        $status = $fiery->fieryServerStatus(false);
        $this->line('  status: ' . ($status ? 'OK' : 'FAIL'));

        $jobs = $fiery->fieryJobs(false);
        $this->line('  jobs: ' . ($jobs !== null ? count($jobs) : 'FAIL'));

        $accounting = $fiery->fieryAccountingPerCommessa(false);
        $this->line('  accounting: ' . ($accounting !== null ? count($accounting) . ' commesse' : 'FAIL'));

        $elapsed = round(microtime(true) - $start, 2);
        $this->info("Done in {$elapsed}s");
        return 0;
    }
}
