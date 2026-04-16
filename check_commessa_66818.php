<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = '0066818-26';
echo "=== COMMESSA {$commessa} ===\n\n";

// 1. Ordini
$ordini = DB::table('ordini')->where('commessa', $commessa)->get();
echo "--- Ordini ---\n";
foreach ($ordini as $o) {
    echo "  ID={$o->id} CodArt={$o->cod_art} Desc=" . substr($o->descrizione ?? '-', 0, 50) . "\n";
}
if ($ordini->isEmpty()) echo "  NESSUN ORDINE TROVATO!\n";

// 2. Fasi (incluse soft-deleted)
$fasi = DB::table('ordine_fasi')
    ->whereIn('ordine_id', $ordini->pluck('id'))
    ->get();
echo "\n--- Fasi (tutte, incluse cancellate) ---\n";
foreach ($fasi as $f) {
    echo "  ID={$f->id} Fase={$f->fase} Stato={$f->stato} Deleted=" . ($f->deleted_at ?? 'NO') . "\n";
}

// 3. Fasi visibili (non cancellate, stato != 4)
$fasiVisibili = DB::table('ordine_fasi')
    ->whereIn('ordine_id', $ordini->pluck('id'))
    ->whereNull('deleted_at')
    ->where('stato', '!=', 4)
    ->get();
echo "\n--- Fasi visibili in dashboard (stato != 4, non cancellate) ---\n";
foreach ($fasiVisibili as $f) {
    echo "  ID={$f->id} Fase={$f->fase} Stato={$f->stato}\n";
}
if ($fasiVisibili->isEmpty()) echo "  NESSUNA — tutte a stato 4 o cancellate\n";

echo "\nDone.\n";
