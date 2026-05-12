<?php

namespace App\Console\Commands;

use App\Models\OrdineFase;
use App\Modules\Prinect\Services\PrinectAccountingService;
use App\Modules\Prinect\ValueObjects\TempiStampa;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PrinectRicalcolaTempi extends Command
{
    protected $signature = 'prinect:ricalcola-tempi
                            {--dal=2026-04-01 : Data inizio storico da rielaborare (YYYY-MM-DD)}
                            {--commessa= : Solo una commessa specifica}
                            {--dry-run : Mostra cosa cambierebbe senza scrivere}';

    protected $description = 'Ricalcola tempo_avviamento_sec/tempo_esecuzione_sec per fasi stampa offset usando workstep.actualTimes Heidelberg (fix bug somma activity raw)';

    public function handle(PrinectAccountingService $acct): int
    {
        ini_set('memory_limit', '1G');
        set_time_limit(0);

        $dal = $this->option('dal');
        $commessaFiltro = $this->option('commessa');
        $dry = (bool) $this->option('dry-run');

        $this->info("Ricalcolo tempi Prinect dal {$dal}" . ($commessaFiltro ? " (solo commessa {$commessaFiltro})" : '') . ($dry ? ' [DRY-RUN]' : ''));

        $q = OrdineFase::with('ordine')
            ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
            ->where(function ($q) {
                $q->where('ordine_fasi.fase', 'LIKE', 'STAMPAXL106%')
                  ->orWhere('ordine_fasi.fase', 'STAMPA');
            })
            ->where('ordini.data_registrazione', '>=', $dal);

        if ($commessaFiltro) {
            $q->where('ordini.commessa', $commessaFiltro);
        }

        $fasi = $q->select('ordine_fasi.*')->get();
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

            $commessa = $fase->ordine->commessa ?? '';
            $jobId    = $this->commessaToJobId($commessa);
            if (! $jobId) {
                $skip++;
                continue;
            }

            $att = DB::table('prinect_attivita')
                ->where('commessa_gestionale', $commessa)
                ->whereNotNull('workstep_name')
                ->first(['workstep_name']);

            if (! $att) {
                $skip++;
                continue;
            }

            try {
                $tempi = $acct->getTempiByWorkstepName($jobId, $att->workstep_name);
            } catch (\Throwable $e) {
                $errori++;
                continue;
            }

            if ($tempi === null) {
                $skip++;
                continue;
            }

            $vecchio = (int) ($fase->tempo_avviamento_sec ?? 0) + (int) ($fase->tempo_esecuzione_sec ?? 0);
            $nuovo   = $tempi->avviamentoSec + $tempi->esecuzioneSec;

            if ($vecchio === $nuovo) continue;

            $variazioni[] = sprintf('%s %s: %.2fh → %.2fh', $commessa, $fase->fase, $vecchio / 3600, $nuovo / 3600);

            if (! $dry) {
                $fase->tempo_avviamento_sec = $tempi->avviamentoSec;
                $fase->tempo_esecuzione_sec = $tempi->esecuzioneSec;
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

    /**
     * Mapping commessa MES → jobId Prinect.
     * Convenzione: primi 7 char della commessa numerici (es. 0067314-26 → 67314).
     */
    private function commessaToJobId(string $commessa): ?string
    {
        $prefix = substr($commessa, 0, 7);
        $jobId  = ltrim($prefix, '0');
        return is_numeric($jobId) ? $jobId : null;
    }
}
