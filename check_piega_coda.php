<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== Coda PIEGA 12-15/05 ===\n";
$rows = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->where('ordine_fasi.sched_macchina', 'PIEGA')
    ->whereNotNull('ordine_fasi.sched_inizio')
    ->whereBetween('ordine_fasi.sched_inizio', ['2026-05-12 00:00', '2026-05-15 23:59'])
    ->whereNull('ordine_fasi.deleted_at')
    ->orderBy('ordine_fasi.sched_inizio')
    ->select(
        'ordini.commessa',
        'ordini.data_prevista_consegna as cons',
        'ordine_fasi.fase',
        'ordine_fasi.stato',
        'ordine_fasi.priorita',
        'ordine_fasi.priorita_manuale as pm',
        'ordine_fasi.sched_inizio',
        'ordine_fasi.sched_fine'
    )
    ->get();

echo "Totale: " . count($rows) . "\n\n";
echo sprintf("%-13s %-10s %-4s %-6s %-2s %-16s %-16s %s\n",
    'commessa', 'fase', 'st', 'pr', 'M', 'inizio', 'fine', 'consegna');
echo str_repeat('-', 100) . "\n";

foreach ($rows as $r) {
    echo sprintf("%-13s %-10s %-4s %-6s %-2s %-16s %-16s %s\n",
        $r->commessa,
        $r->fase,
        $r->stato,
        $r->priorita ?? '-',
        $r->pm ? 'M' : '-',
        substr($r->sched_inizio, 0, 16),
        substr($r->sched_fine ?? '-', 0, 16),
        $r->cons ?? '-'
    );
}

echo "\n=== Stato coda PIEGA totale (tutte le date) ===\n";
$tot = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->where('ordine_fasi.sched_macchina', 'PIEGA')
    ->whereNotNull('ordine_fasi.sched_inizio')
    ->whereNull('ordine_fasi.deleted_at')
    ->selectRaw('DATE(ordine_fasi.sched_inizio) as g, COUNT(*) as n')
    ->groupBy('g')
    ->orderBy('g')
    ->get();
foreach ($tot as $t) {
    echo "{$t->g}: {$t->n} fasi\n";
}
