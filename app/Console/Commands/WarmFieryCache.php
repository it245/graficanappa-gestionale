<?php

namespace App\Console\Commands;

use App\Http\Services\FieryService;
use Illuminate\Console\Command;

class WarmFieryCache extends Command
{
    protected $signature = 'fiery:warm';
    protected $description = 'Popola cache Fiery in background con timeout lunghi (run via cron/Task Scheduler)';

    public function handle(FieryService $fiery)
    {
        $start = microtime(true);
        $this->info('Fiery warm cache start...');

        $status = $fiery->getServerStatus(false);
        $this->line('  status: ' . ($status ? 'OK' : 'FAIL'));

        $jobs = $fiery->getJobs(false);
        $this->line('  jobs: ' . ($jobs !== null ? count($jobs) : 'FAIL'));

        $accounting = $fiery->getAccountingPerCommessa(false);
        $this->line('  accounting: ' . ($accounting !== null ? count($accounting) . ' commesse' : 'FAIL'));

        $elapsed = round(microtime(true) - $start, 2);
        $this->info("Done in {$elapsed}s");
        return 0;
    }
}
