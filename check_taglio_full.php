<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;

$taglio = OrdineFase::whereIn('fase', ['TAGLIACARTE','TAGLIACARTE.IML','TAGLIOINDIGO'])
    ->where('stato', '<', 3)
    ->get(['id','fase','stato','sched_macchina','sched_inizio','sched_fine']);

echo "Tot fasi Tagliacarte non terminate: " . $taglio->count() . "\n";
echo "Con sched_inizio: " . $taglio->whereNotNull('sched_inizio')->count() . "\n";
echo "Senza sched_inizio (fallback UI): " . $taglio->whereNull('sched_inizio')->count() . "\n";

echo "\nPrime 5 senza sched:\n";
foreach ($taglio->whereNull('sched_inizio')->take(5) as $f) {
    echo "  id={$f->id} fase={$f->fase} stato={$f->stato}\n";
}

echo "\nPrime 5 con sched:\n";
foreach ($taglio->whereNotNull('sched_inizio')->take(5) as $f) {
    echo "  id={$f->id} fase={$f->fase} sched={$f->sched_inizio}\n";
}
