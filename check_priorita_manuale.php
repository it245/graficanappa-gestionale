<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = $argv[1] ?? null;
if (!$commessa) {
    // Mostra ultime 20 fasi modificate con priorita_manuale
    echo "=== Ultime 20 fasi modificate (priorità) ===\n";
    $rows = DB::table('ordine_fasi as f')
        ->join('ordini as o', 'o.id', '=', 'f.ordine_id')
        ->select('o.commessa', 'f.id', 'f.fase', 'f.priorita', 'f.priorita_manuale', 'f.updated_at')
        ->whereNull('f.deleted_at')
        ->orderBy('f.updated_at', 'desc')
        ->limit(20)
        ->get();
    foreach ($rows as $r) {
        echo "  comm={$r->commessa} id={$r->id} fase={$r->fase} prior={$r->priorita} manuale=" .
             ($r->priorita_manuale ? 'SI' : 'NO') . " upd={$r->updated_at}\n";
    }
    exit;
}

echo "=== Fasi commessa {$commessa} ===\n";
$rows = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'o.id', '=', 'f.ordine_id')
    ->where('o.commessa', 'LIKE', "%{$commessa}%")
    ->select('f.id', 'f.fase', 'f.stato', 'f.priorita', 'f.priorita_manuale', 'f.updated_at')
    ->whereNull('f.deleted_at')
    ->orderBy('f.id')
    ->get();
foreach ($rows as $r) {
    echo "  id={$r->id} fase={$r->fase} stato={$r->stato} prior={$r->priorita} manuale=" .
         ($r->priorita_manuale ? 'SI' : 'NO') . " upd={$r->updated_at}\n";
}
