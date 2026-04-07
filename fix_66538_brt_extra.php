<?php
// Fix 66538: rimuovi BRT1 extra (deve essercene solo 1 per commessa)
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = '0066538-26';

// Trova tutte le BRT1 della commessa
$brtFasi = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->where('ordini.commessa', $commessa)
    ->where('ordine_fasi.fase', 'BRT1')
    ->whereNull('ordine_fasi.deleted_at')
    ->select('ordine_fasi.id', 'ordini.cod_art')
    ->orderBy('ordine_fasi.id')
    ->get();

echo "BRT1 trovate: {$brtFasi->count()}\n";
foreach ($brtFasi as $b) {
    echo "  ID:{$b->id} | Art:{$b->cod_art}\n";
}

if ($brtFasi->count() <= 1) {
    echo "Nessun duplicato da rimuovere.\n";
    exit(0);
}

// Tieni la prima, rimuovi le altre
$daRimuovere = $brtFasi->skip(1)->pluck('id')->toArray();
DB::table('ordine_fasi')->whereIn('id', $daRimuovere)->update(['deleted_at' => now()]);

echo "\nRimosse " . count($daRimuovere) . " BRT1 extra (tenuta ID:{$brtFasi->first()->id})\n";
