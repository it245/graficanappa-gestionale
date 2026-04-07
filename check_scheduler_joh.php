<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

error_reporting(E_ALL & ~E_DEPRECATED);

// Tutte le fasi JOH schedulate, in ordine
echo "=== FASI JOH SCHEDULATE (in ordine) ===\n";
$johs = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->where('sched_macchina', 'JOH')
    ->orderBy('sched_posizione')
    ->select('ordine_fasi.sched_posizione', 'ordini.commessa', 'ordine_fasi.fase',
             'ordine_fasi.sched_inizio', 'ordine_fasi.sched_fine', 'ordine_fasi.stato')
    ->get();

foreach ($johs as $j) {
    echo "  #{$j->sched_posizione} {$j->commessa} {$j->fase} stato={$j->stato} | {$j->sched_inizio} → {$j->sched_fine}\n";
}

// Commessa 66840 - tutte le fasi
echo "\n=== COMMESSA 0066840-26 — TUTTE LE FASI ===\n";
$fasi = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->where('ordini.commessa', '0066840-26')
    ->select('ordine_fasi.fase', 'ordine_fasi.stato', 'ordine_fasi.sched_macchina',
             'ordine_fasi.sched_inizio', 'ordine_fasi.sched_fine', 'ordine_fasi.sched_posizione',
             'ordini.descrizione')
    ->orderBy('ordine_fasi.sched_inizio')
    ->get();

foreach ($fasi as $f) {
    $mac = $f->sched_macchina ?: 'N/A';
    $inizio = $f->sched_inizio ?: 'non sched';
    $fine = $f->sched_fine ?: 'non sched';
    echo "  {$f->fase} stato={$f->stato} mac={$mac} | {$inizio} → {$fine}\n";
    echo "    desc: " . mb_substr($f->descrizione, 0, 60) . "\n";
}
