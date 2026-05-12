<?php

namespace App\Console\Commands;

use App\Http\Services\PrinectService;
use App\Models\OrdineFase;
use App\Models\PrinectAttivita;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PrinectRicalcolaTempi extends Command
{
    protected $signature = 'prinect:ricalcola-tempi
                            {--dal=2026-04-01 : Data inizio storico da rielaborare (YYYY-MM-DD)}
                            {--commessa= : Solo una commessa specifica}
                            {--dry-run : Mostra cosa cambierebbe senza scrivere}';

    protected $description = 'Ricalcola tempo_avviamento_sec/tempo_esecuzione_sec per fasi stampa offset usando workstep.actualTimes Heidelberg (fix bug somma activity raw)';

    private array $cache = [];

    public function handle(PrinectService $prinect): int
    {
        ini_set('memory_limit', '1G');
        set_time_limit(0);

        $dal = $this->option('dal');
        $commessaFiltro = $this->option('commessa');
        $dry = (bool) $this->option('dry-run');

        $this->info("Ricalcolo tempi Prinect dal {$dal}" . ($commessaFiltro ? " (solo {$commessaFiltro})" : '') . ($dry ? ' [DRY-RUN]' : ''));

        $q = OrdineFase::join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
            ->where(function ($q) {
                $q->where('ordine_fasi.fase', 'LIKE', 'STAMPAXL106%')
                  ->orWhere('ordine_fasi.fase', 'STAMPA');
            })
            ->where('ordini.data_registrazione', '>=', $dal);

        if ($commessaFiltro) {
            $q->where('ordini.commessa', $commessaFiltro);
        }

        $fasi = $q->select('ordine_fasi.*', 'ordini.commessa as _commessa')->get();
        $this->info('Fasi candidate: ' . $fasi->count());

        $bar = $this->output->createProgressBar($fasi->count());
        $bar->setFormat('verbose');
        $bar->start();

        $aggiornate = 0;
        $skip = 0;
        $errori = 0;
        $variazioni = [];

        foreach ($fasi as $fase) {
            $bar->advance();

            $commessa = $fase->_commessa ?? '';
            $jobId    = $this->commessaToJobId($commessa);
            if (! $jobId) {
                $skip++;
                continue;
            }

            $att = PrinectAttivita::where('commessa_gestionale', $commessa)
                ->whereNotNull('workstep_name')
                ->first(['workstep_name']);
            if (! $att) {
                $skip++;
                continue;
            }

            try {
                [$secAvv, $secProd] = $this->actualTimes($prinect, $jobId, $att->workstep_name);
            } catch (\Throwable $e) {
                $errori++;
                continue;
            }

            if ($secAvv === 0 && $secProd === 0) {
                $skip++;
                continue;
            }

            $vecchio = (int) ($fase->tempo_avviamento_sec ?? 0) + (int) ($fase->tempo_esecuzione_sec ?? 0);
            $nuovo   = $secAvv + $secProd;

            if ($vecchio === $nuovo) continue;

            $variazioni[] = sprintf('%s %s: %.2fh → %.2fh', $commessa, $fase->fase, $vecchio / 3600, $nuovo / 3600);

            if (! $dry) {
                $fase->tempo_avviamento_sec = $secAvv;
                $fase->tempo_esecuzione_sec = $secProd;
                $fase->save();
            }

            $aggiornate++;
        }

        $bar->finish();
        $this->newLine(2);

        $maxShow = 20;
        if (count($variazioni) > 0) {
            $this->info('Variazioni (prime ' . min($maxShow, count($variazioni)) . '):');
            foreach (array_slice($variazioni, 0, $maxShow) as $v) {
                $this->line('  ' . $v);
            }
            if (count($variazioni) > $maxShow) {
                $this->line('  ... +' . (count($variazioni) - $maxShow) . ' altre');
            }
        }

        $this->info("Aggiornate: {$aggiornate}");
        $this->info("Skip: {$skip}");
        $this->info("Errori: {$errori}");

        if ($dry) {
            $this->warn('DRY-RUN: nessuna modifica scritta. Rilancia senza --dry-run per applicare.');
        }

        return 0;
    }

    private function actualTimes(PrinectService $prinect, string $jobId, string $workstepName): array
    {
        if (!isset($this->cache[$jobId])) {
            $this->cache[$jobId] = $prinect->getJobWorksteps($jobId);
        }
        $worksteps = $this->cache[$jobId];
        if (!is_array($worksteps) || empty($worksteps['worksteps'])) return [0, 0];

        foreach ($worksteps['worksteps'] as $w) {
            if (($w['name'] ?? null) !== $workstepName) continue;
            $secAvv = 0;
            $secProd = 0;
            foreach (($w['actualTimes'] ?? []) as $t) {
                $name = mb_strtolower($t['timeTypeName'] ?? '');
                $dur  = (int) ($t['duration'] ?? 0);
                if ($dur <= 0) continue;
                if (str_contains($name, 'avviamento')) {
                    $secAvv += $dur;
                } elseif (str_contains($name, 'esecuzione') || str_contains($name, 'produzione')) {
                    $secProd += $dur;
                }
            }
            return [$secAvv, $secProd];
        }
        return [0, 0];
    }

    private function commessaToJobId(string $commessa): ?string
    {
        $prefix = substr($commessa, 0, 7);
        $jobId  = ltrim($prefix, '0');
        return is_numeric($jobId) ? $jobId : null;
    }
}
