<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$commesse = ['0067129-26', '0067317-26', '0067334-26', '0067335-26', '0067336-26', '0066341-26'];
$ordine = require __DIR__ . '/config/fasi_priorita.php';

echo "\n=== Diagnosi gap Piegaincolla ===\n";
echo "Sequenza fasi (config/fasi_priorita.php): " . count($ordine) . " fasi mappate\n\n";

foreach ($commesse as $comm) {
    echo str_repeat('=', 80) . "\n";
    echo "COMMESSA $comm\n";
    echo str_repeat('=', 80) . "\n";

    $fasi = DB::table('ordine_fasi')
        ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
        ->where('ordini.commessa', $comm)
        ->whereNull('ordine_fasi.deleted_at')
        ->select(
            'ordine_fasi.id',
            'ordine_fasi.fase',
            'ordine_fasi.stato',
            'ordine_fasi.priorita',
            'ordine_fasi.priorita_manuale',
            'ordine_fasi.disponibile',
            'ordine_fasi.disponibile_m37',
            'ordine_fasi.sched_inizio',
            'ordine_fasi.sched_fine',
            'ordine_fasi.sched_macchina',
            'ordini.data_prevista_consegna'
        )
        ->get()
        ->sortBy(fn($f) => $ordine[$f->fase] ?? 999);

    if ($fasi->isEmpty()) {
        echo "  (nessuna fase)\n\n";
        continue;
    }

    $cons = $fasi->first()->data_prevista_consegna;
    echo "Consegna: $cons\n\n";
    echo sprintf("%-3s %-22s %-4s %-5s %-7s %-7s %-16s %-16s %s\n",
        'seq', 'fase', 'stato', 'pr', 'manuale', 'disp', 'sched_inizio', 'sched_fine', 'macchina');
    echo str_repeat('-', 110) . "\n";

    foreach ($fasi as $f) {
        $seq = $ordine[$f->fase] ?? '?';
        echo sprintf("%-3s %-22s %-4s %-5s %-7s %-7s %-16s %-16s %s\n",
            $seq,
            substr($f->fase, 0, 22),
            $f->stato,
            $f->priorita ?? '-',
            $f->priorita_manuale ? 'SI' : '-',
            $f->disponibile ? 'D' : 'X',
            $f->sched_inizio ? substr($f->sched_inizio, 0, 16) : '-',
            $f->sched_fine ? substr($f->sched_fine, 0, 16) : '-',
            $f->sched_macchina ?? '-'
        );
    }

    // Identifica fase PI e i suoi predecessori (seq < pi)
    $piFasi = $fasi->filter(fn($f) => str_starts_with($f->fase, 'PI'));
    foreach ($piFasi as $pi) {
        $seqPi = $ordine[$pi->fase] ?? 999;
        echo "\n  Fase PI: {$pi->fase} (seq=$seqPi, stato={$pi->stato}, disp=" . ($pi->disponibile?'SI':'NO') . ")\n";
        echo "  Sched: " . ($pi->sched_inizio ?? '-') . " → " . ($pi->sched_fine ?? '-') . "\n";

        $predecessori = $fasi->filter(fn($f) => ($ordine[$f->fase] ?? 999) < $seqPi);
        $maxFinePred = null;
        $bloccanti = [];
        foreach ($predecessori as $p) {
            $fineP = $p->sched_fine ?? null;
            if ($p->stato < 3) {
                $bloccanti[] = $p->fase . " (st={$p->stato}, fine=" . ($fineP ?? '-') . ")";
            }
            if ($fineP && (!$maxFinePred || $fineP > $maxFinePred)) {
                $maxFinePred = $fineP;
            }
        }

        echo "  Max fine predecessori (sched): " . ($maxFinePred ?? '-') . "\n";
        echo "  Predecessori non terminati: " . (count($bloccanti) > 0 ? implode(', ', $bloccanti) : '(nessuno)') . "\n";

        if ($maxFinePred && $pi->sched_inizio && $maxFinePred < $pi->sched_inizio) {
            $gapH = round((strtotime($pi->sched_inizio) - strtotime($maxFinePred)) / 3600, 1);
            echo "  ⚠ GAP: predecessori finiscono $maxFinePred ma PI parte {$pi->sched_inizio} (gap={$gapH}h)\n";
        }
    }
    echo "\n";
}
