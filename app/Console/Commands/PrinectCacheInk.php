<?php

namespace App\Console\Commands;

use App\Jobs\CachePrinectInkJob;
use App\Models\OrdineFase;
use Illuminate\Console\Command;

class PrinectCacheInk extends Command
{
    protected $signature = 'prinect:cache-ink {--commessa= : Singola commessa} {--sync : Esegui sincrono senza queue}';
    protected $description = 'Popola cache inchiostro Prinect per fasi stampa offset terminate';

    public function handle(): int
    {
        if ($c = $this->option('commessa')) {
            $this->dispatch($c);
            $this->info("Dispatch inchiostro per {$c}");
            return 0;
        }

        $rows = \Illuminate\Support\Facades\DB::select("
            SELECT DISTINCT o.commessa
            FROM ordine_fasi f
            INNER JOIN ordini o ON o.id = f.ordine_id
            WHERE f.stato = 3
              AND (f.fase LIKE 'STAMPAXL106%' OR f.fase LIKE 'STAMPA XL%' OR f.fase = 'STAMPA')
              AND o.commessa IS NOT NULL
        ");
        $commesse = collect($rows)->pluck('commessa')->filter()->unique()->values();

        $this->info("Commesse da cacheare: {$commesse->count()}");
        $bar = $this->output->createProgressBar($commesse->count());
        foreach ($commesse as $c) {
            $this->dispatch($c);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info($this->option('sync') ? 'Completato (sync).' : 'Jobs accodati. Avvia: php artisan queue:work');
        return 0;
    }

    private function dispatch(string $commessa): void
    {
        if ($this->option('sync')) {
            (new CachePrinectInkJob($commessa))->handle(app(\App\Http\Services\PrinectService::class));
        } else {
            CachePrinectInkJob::dispatch($commessa);
        }
    }
}
