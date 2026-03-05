<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = $argv[1] ?? '0066648-26';

$ordini = DB::table('ordini')
    ->where('commessa', $commessa)
    ->select('id', 'commessa', 'cod_art', 'descrizione')
    ->get();

echo "Ordini per commessa {$commessa}:\n";
foreach ($ordini as $o) {
    $desc = substr($o->descrizione ?? '', 0, 60);
    echo "ID:{$o->id} | Art:{$o->cod_art} | {$desc}\n";

    $fasi = DB::table('ordine_fasi')
        ->where('ordine_id', $o->id)
        ->select('id', 'fase', 'stato', 'deleted_at')
        ->get();
    foreach ($fasi as $f) {
        $del = $f->deleted_at ? ' [DELETED]' : '';
        echo "  Fase ID:{$f->id} | {$f->fase} | stato:{$f->stato}{$del}\n";
    }
}
echo "\nTotale ordini: " . count($ordini) . "\n";
