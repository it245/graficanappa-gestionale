<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'o.id', 'f.ordine_id')
    ->where('o.commessa', '0067201-26')
    ->select('f.id', 'f.fase', 'f.stato', 'f.deleted_at', 'o.descrizione')
    ->orderBy('f.id')
    ->get();
echo "=== Fasi 67201 (tutte, attive) ===\n";
foreach ($rows as $r) {
    $del = $r->deleted_at ? '🗑️' : '✓';
    echo "  {$del} id={$r->id} | {$r->fase} | stato='{$r->stato}' | " . substr($r->descrizione ?? '', 0, 40) . "\n";
}
echo "\nTotale attive: " . $rows->whereNull('deleted_at')->count() . "\n";
echo "Non >=3: " . $rows->filter(fn($r) => !$r->deleted_at && (int)$r->stato < 3)->count() . "\n";
