<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = $argv[1] ?? '0066648-26';

$fasi = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->where('ordini.commessa', $commessa)
    ->select('ordine_fasi.id','ordine_fasi.fase','ordine_fasi.stato','ordine_fasi.deleted_at','ordine_fasi.manuale')
    ->get();

echo "Fasi DB per commessa {$commessa} (incluse soft-deleted):\n";
foreach ($fasi as $f) {
    echo "ID:{$f->id} | {$f->fase} | stato:{$f->stato} | deleted:{$f->deleted_at} | manuale:{$f->manuale}\n";
}
echo "\nTotale: " . count($fasi) . "\n";
