<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$ids = [15707, 23097, 23098, 23095, 23094, 23096];

echo "=== Fasi con deleted_at ===\n";
$rows = DB::table('ordine_fasi')
    ->whereIn('id', $ids)
    ->select('id', 'fase', 'stato', 'deleted_at', 'data_inizio', 'data_fine')
    ->get();

foreach ($rows as $r) {
    $del = $r->deleted_at ?? '(NULL)';
    echo "  id={$r->id} | {$r->fase} | stato={$r->stato} | deleted_at={$del}\n";
}

// Cerca anche fasi STAMPAINDIGO copertina commessa 67343
echo "\n=== Cerca fase STAMPA su commessa 67343 (libro interno) ===\n";
$f = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'o.id', 'f.ordine_id')
    ->where('o.commessa', '0067343-26')
    ->where('o.cod_art', 'Libri')
    ->select('f.id', 'f.fase', 'f.stato', 'f.deleted_at')
    ->get();
foreach ($f as $r) {
    echo "  id={$r->id} | {$r->fase} | stato={$r->stato} | deleted_at=" . ($r->deleted_at ?? 'NULL') . "\n";
}
if ($f->isEmpty()) echo "  (nessuna fase per libro interno 67343)\n";
