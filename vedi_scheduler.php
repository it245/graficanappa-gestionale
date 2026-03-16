<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$totSched = DB::table('ordine_fasi')->whereNotNull('sched_posizione')->count();
$totDisp = DB::table('ordine_fasi')->where('disponibile_m37', true)->count();
$totFasce = DB::table('ordine_fasi')->whereNotNull('fascia_urgenza')->selectRaw('fascia_urgenza, COUNT(*) as cnt')->groupBy('fascia_urgenza')->orderBy('fascia_urgenza')->get();

echo "=== RISULTATI SCHEDULER MOSSA 37 ===" . PHP_EOL;
echo "Fasi schedulate: $totSched" . PHP_EOL;
echo "Fasi disponibili (m37): $totDisp" . PHP_EOL;
echo PHP_EOL . "Fasce urgenza:" . PHP_EOL;
$labels = ['0' => 'CRITICA', '1' => 'URGENTE', '2' => 'NORMALE', '3' => 'PIANIFICABILE'];
foreach ($totFasce as $f) {
    echo "  " . ($labels[$f->fascia_urgenza] ?? $f->fascia_urgenza) . ": {$f->cnt}" . PHP_EOL;
}

// Per ogni macchina
echo PHP_EOL . "=== PER MACCHINA ===" . PHP_EOL;
$macchine = DB::table('ordine_fasi')->whereNotNull('sched_macchina')
    ->selectRaw('sched_macchina, COUNT(*) as cnt, MIN(sched_inizio) as primo, MAX(sched_fine) as ultimo')
    ->groupBy('sched_macchina')->orderBy('sched_macchina')->get();

foreach ($macchine as $m) {
    echo PHP_EOL . "--- {$m->sched_macchina} ({$m->cnt} fasi) ---" . PHP_EOL;
    echo "  Da: {$m->primo}  A: {$m->ultimo}" . PHP_EOL;

    // Prime 10 fasi
    $fasi = DB::table('ordine_fasi')
        ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
        ->where('sched_macchina', $m->sched_macchina)
        ->select('sched_posizione', 'ordini.commessa', 'ordini.cliente_nome', 'ordine_fasi.fase',
                 'sched_inizio', 'sched_fine', 'sched_setup_tipo', 'sched_batch_group',
                 'ordine_fasi.fascia_urgenza', 'ordine_fasi.urgenza_reale')
        ->orderBy('sched_posizione')
        ->limit(10)
        ->get();

    foreach ($fasi as $f) {
        $urg = $labels[$f->fascia_urgenza] ?? '?';
        $cliente = mb_substr($f->cliente_nome ?? '', 0, 20);
        echo "  #{$f->sched_posizione} {$f->commessa} {$cliente} | {$f->fase} | {$f->sched_inizio} → {$f->sched_fine} | {$f->sched_setup_tipo} | urg:{$urg} ({$f->urgenza_reale}gg)" . PHP_EOL;
    }
    if ($m->cnt > 10) echo "  ... e altre " . ($m->cnt - 10) . " fasi" . PHP_EOL;
}
