<?php

namespace App\Console\Commands;

use App\Models\OrdineFase;
use App\Services\PriorityService;
use Illuminate\Console\Command;

class RicalcolaDisponibile extends Command
{
    protected $signature = 'fasi:ricalcola-disponibile {--dry-run}';

    protected $description = 'Ricalcola flag disponibile su tutte le fasi attive (fix propagazione mancata)';

    public function handle(): int
    {
        ini_set('memory_limit', '1G');
        set_time_limit(0);

        $dry = (bool) $this->option('dry-run');

        $fasi = OrdineFase::with('ordine')
            ->whereNull('deleted_at')
            ->whereIn('stato', ['0', '1', '2'])
            ->get();

        $this->info('Fasi attive analizzate: ' . $fasi->count());

        // Raggruppa per commessa per calcolo predecessori efficace
        $perCommessa = $fasi->groupBy(fn ($f) => $f->ordine->commessa ?? '');

        $cambiate = 0;
        $bar = $this->output->createProgressBar($fasi->count());
        $bar->start();

        foreach ($fasi as $fase) {
            $bar->advance();
            $commessa = $fase->ordine->commessa ?? '';
            if (! $commessa) continue;

            $tutteFasiCommessa = $perCommessa[$commessa] ?? collect();
            $vecchio = (bool) $fase->disponibile;
            PriorityService::calcolaDisponibile($fase, $tutteFasiCommessa);
            $nuovo = (bool) $fase->disponibile;

            if ($vecchio !== $nuovo) {
                $cambiate++;
                if (! $dry) {
                    OrdineFase::where('id', $fase->id)->update(['disponibile' => $nuovo]);
                }
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Fasi con flag disponibile cambiato: {$cambiate}");
        if ($dry) {
            $this->warn('DRY-RUN: niente scritto. Rilancia senza --dry-run per applicare.');
        }

        return 0;
    }
}
