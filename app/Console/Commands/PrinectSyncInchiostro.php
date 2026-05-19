<?php

namespace App\Console\Commands;

use App\Http\Services\PrinectService;
use App\Models\OrdineFase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PrinectSyncInchiostro extends Command
{
    protected $signature = 'prinect:sync-inchiostro {--giorni=30} {--commessa=}';
    protected $description = 'Popola ordine_fasi.inchiostro_g da Prinect API per fasi STAMPA terminate';

    public function handle(PrinectService $prinect): int
    {
        $giorni = (int) $this->option('giorni');
        $commessaFilter = $this->option('commessa');

        $q = OrdineFase::with('ordine')
            ->whereIn('stato', ['3', '4'])
            ->where(function ($q) {
                $q->where('fase', 'LIKE', 'STAMPAXL106%')
                  ->orWhere('fase', 'STAMPA')
                  ->orWhere('fase', 'LIKE', 'STAMPA XL%');
            })
            ->whereNull('inchiostro_g')
            ->where('data_fine', '>=', now()->subDays($giorni));

        if ($commessaFilter) {
            $q->whereHas('ordine', fn($q2) => $q2->where('commessa', 'LIKE', "%{$commessaFilter}%"));
        }

        $fasi = $q->get();
        $this->info("Fasi da processare: " . $fasi->count());

        // Raggruppa per commessa: 1 chiamata API per commessa
        $perCommessa = $fasi->groupBy(fn($f) => $f->ordine?->commessa);

        $ok = 0;
        $fail = 0;
        foreach ($perCommessa as $commessa => $faseList) {
            if (!$commessa) continue;
            $jobId = ltrim(explode('-', $commessa)[0] ?? '', '0');
            if (!$jobId || !is_numeric($jobId)) continue;

            try {
                $jobData = $prinect->getJobWorksteps((int) $jobId);
                $worksteps = collect($jobData['worksteps'] ?? [])
                    ->filter(fn($ws) => in_array('ConventionalPrinting', $ws['types'] ?? []));

                // Calcola g totali per commessa
                $totalG = 0.0;
                foreach ($worksteps as $ws) {
                    $produced = (int) ($ws['amountProduced'] ?? 0);
                    if ($produced <= 0) continue;
                    $ink = $prinect->getWorkstepInkConsumption((int) $jobId, $ws['id']);
                    foreach (($ink['inkConsumptions'] ?? []) as $i) {
                        $totalG += ((float) ($i['estimatedConsumption'] ?? 0)) * $produced;
                    }
                }
                $totalG = round($totalG, 2);

                if ($totalG <= 0) { $fail++; continue; }

                // Distribuisci proporzionale ai buoni se più fasi STAMPAXL per stessa commessa
                $totBuoni = (int) $faseList->sum('fogli_buoni');
                foreach ($faseList as $fase) {
                    if ($totBuoni > 0 && count($faseList) > 1) {
                        $quota = ($fase->fogli_buoni ?? 0) / $totBuoni;
                        $fase->inchiostro_g = round($totalG * $quota, 2);
                    } else {
                        $fase->inchiostro_g = $totalG;
                    }
                    $fase->save();
                }

                $ok++;
                $this->line("  {$commessa}: {$totalG} g distribuiti su " . count($faseList) . " fasi");
            } catch (\Throwable $e) {
                Log::error("Prinect sync inchiostro fallito {$commessa}: " . $e->getMessage());
                $fail++;
            }
        }

        $this->info("Completato. OK: {$ok} | Fail: {$fail}");
        return 0;
    }
}
