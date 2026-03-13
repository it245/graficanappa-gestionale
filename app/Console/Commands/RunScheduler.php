<?php

namespace App\Console\Commands;

use App\Services\SchedulerService;
use Illuminate\Console\Command;

class RunScheduler extends Command
{
    protected $signature = 'scheduler:run';
    protected $description = 'Esegue lo scheduler Mossa 37 — simulazione a eventi discreti';

    public function handle()
    {
        $this->info('Scheduler Mossa 37 in esecuzione...');
        $start = microtime(true);

        try {
            $service = new SchedulerService();
            $result = $service->esegui();
        } catch (\Throwable $e) {
            $this->error("Errore: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return 1;
        }

        $elapsed = round(microtime(true) - $start, 2);

        $this->info("Fasi caricate: {$result['fasi']}");
        $this->info("Fasi schedulate: {$result['schedulate']}");
        $this->info("Fasi propagate: " . ($result['propagate'] ?? 0));

        if (!empty($result['per_macchina'])) {
            $this->info('Per macchina:');
            foreach ($result['per_macchina'] as $mac => $n) {
                $this->line("  $mac: $n");
            }
        }

        $this->info("Tempo: {$elapsed}s");
        return 0;
    }
}
