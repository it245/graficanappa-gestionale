<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = $argv[1] ?? '0066942-26';

echo "=== Ordini commessa {$commessa} ===\n";
$ordini = DB::table('ordini')->where('commessa', $commessa)->get();
foreach ($ordini as $o) echo "  id={$o->id} desc='" . mb_substr($o->descrizione,0,60) . "'\n";

echo "\n=== Fasi ===\n";
$fasi = DB::table('ordine_fasi')->whereIn('ordine_id', $ordini->pluck('id'))->get();
foreach ($fasi as $f) {
    echo "  fase_id={$f->id} fase={$f->fase} stato={$f->stato} esterno=" .
         ($f->esterno ? '1' : '0') . " ddt_fornitore_id=" . ($f->ddt_fornitore_id ?? 'NULL') . "\n";
    if ($f->note) echo "     note: " . mb_substr($f->note, 0, 80) . "\n";
}

echo "\n=== DDT fornitori collegati ===\n";
if (\Illuminate\Support\Facades\Schema::hasTable('ddt_fornitori')) {
    $ddts = DB::table('ddt_fornitori')
        ->where(function($q) use ($commessa) {
            $q->where('note', 'like', "%{$commessa}%")
              ->orWhere('riferimento', 'like', "%{$commessa}%");
        })->get();
    foreach ($ddts as $d) echo "  ddt_id={$d->id} fornitore=" . ($d->fornitore ?? '-') . "\n";
    if ($ddts->isEmpty()) echo "  (nessuno trovato by note/riferimento)\n";
} else {
    echo "  tabella ddt_fornitori non esiste\n";
}
