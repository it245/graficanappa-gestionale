<?php

namespace App\Console\Commands;

use App\Services\SchedulerService;
use App\Services\SchedulerExportService;
use App\Mail\PianoProduzione;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class RunScheduler extends Command
{
    protected $signature = 'scheduler:run {--email : Genera Excel e invia email con piano produzione} {--to= : Indirizzo email destinatario (default: anappa@graficanappa.com)}';
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

        // Genera Excel e invia email
        if ($this->option('email')) {
            $path = storage_path('app/piano_produzione_' . now()->format('Y-m-d') . '.xlsx');

            try {
                SchedulerExportService::export($path);
                $this->info("Excel generato: $path");

                $to = $this->option('to') ?: 'anappa@graficanappa.com';
                Mail::to($to)->send(new PianoProduzione($path, $result));
                $this->info("Email inviata a $to");
            } catch (\Throwable $e) {
                $this->error("Errore export/email: {$e->getMessage()}");
            }
        }

        return 0;
    }
}
