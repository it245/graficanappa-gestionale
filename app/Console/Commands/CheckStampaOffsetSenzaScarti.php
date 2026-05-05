<?php

namespace App\Console\Commands;

use App\Models\OrdineFase;
use Illuminate\Console\Command;

class CheckStampaOffsetSenzaScarti extends Command
{
    protected $signature = 'check:stampa-offset-senza-scarti
                            {--reparto=stampa offset : Reparto da controllare}
                            {--giorni=30 : Filtra fasi terminate negli ultimi N giorni}';

    protected $description = 'Conta + lista fasi stato 3 nel reparto specificato senza scarti registrati';

    public function handle(): int
    {
        $reparto = $this->option('reparto');
        $giorni  = (int) $this->option('giorni');

        $base = OrdineFase::whereHas('faseCatalogo.reparto', fn($r) => $r->where('nome', $reparto))
            ->where('stato', 3);

        if ($giorni > 0) {
            $base->where('data_fine', '>=', now()->subDays($giorni));
        }

        $totale     = (clone $base)->count();
        $senzaCount = (clone $base)->where(function ($w) {
            $w->whereNull('scarti')->orWhere('scarti', 0);
        })->count();
        $conCount = (clone $base)->where('scarti', '>', 0)->count();

        $this->newLine();
        $this->line("<fg=cyan>=== Reparto: {$reparto} | Stato 3 | Ultimi {$giorni}gg ===</>");
        $this->line("Totale fasi:           " . $totale);
        $this->line("<fg=red>Senza scarti (NULL/0): " . $senzaCount . "</>");
        $this->line("<fg=green>Con scarti (>0):       " . $conCount . "</>");
        $this->newLine();

        if ($senzaCount === 0) {
            $this->info('Nessuna fase senza scarti. ✓');
            return 0;
        }

        $fasi = (clone $base)->with('ordine:id,commessa,cliente_nome,descrizione')
            ->where(function ($w) { $w->whereNull('scarti')->orWhere('scarti', 0); })
            ->orderBy('data_fine', 'desc')
            ->get(['id', 'ordine_id', 'fase', 'qta_prod', 'scarti', 'data_fine']);

        $rows = $fasi->map(fn($f) => [
            'ID fase'   => $f->id,
            'Commessa'  => $f->ordine->commessa ?? '-',
            'Cliente'   => mb_strimwidth($f->ordine->cliente_nome ?? '-', 0, 24, '…'),
            'Fase'      => $f->fase,
            'Qta prod'  => $f->qta_prod ?? 0,
            'Scarti'    => $f->scarti ?? 'NULL',
            'Data fine' => $f->data_fine?->format('d/m/Y H:i') ?? '-',
        ])->toArray();

        $this->table(
            ['ID fase', 'Commessa', 'Cliente', 'Fase', 'Qta prod', 'Scarti', 'Data fine'],
            $rows
        );

        return 0;
    }
}
