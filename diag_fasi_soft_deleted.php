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

// Cerca TUTTE fasi 67343 incluse soft-deleted
echo "\n=== TUTTE fasi 67343 (incluse soft-deleted) ===\n";
$f = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'o.id', 'f.ordine_id')
    ->where('o.commessa', '0067343-26')
    ->select('f.id', 'f.fase', 'f.stato', 'f.deleted_at', 'o.cod_art', 'o.descrizione')
    ->orderBy('f.id')
    ->get();
foreach ($f as $r) {
    $del = $r->deleted_at ? '🗑️ DEL ' . $r->deleted_at : '✓ ATTIVA';
    echo "  id={$r->id} | art={$r->cod_art} | {$r->fase} | stato={$r->stato} | {$del}\n";
}
if ($f->isEmpty()) echo "  (nessuna fase 67343)\n";
