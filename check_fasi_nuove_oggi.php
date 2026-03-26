<?php
// Trova fasi create oggi (created_at oggi) su ordini con data_registrazione vecchia
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$oggi = date('Y-m-d');
echo "=== FASI CREATE OGGI ({$oggi}) ===\n\n";

$fasi = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'f.ordine_id', '=', 'o.id')
    ->whereDate('f.created_at', $oggi)
    ->whereNull('f.deleted_at')
    ->select('f.id', 'f.fase', 'f.stato', 'f.created_at', 'o.commessa', 'o.cliente_nome', 'o.data_registrazione')
    ->orderBy('f.created_at', 'desc')
    ->get();

echo "Totale fasi create oggi: {$fasi->count()}\n\n";

// Raggruppa per commessa
$perCommessa = $fasi->groupBy('commessa');
foreach ($perCommessa as $comm => $fasiComm) {
    $prima = $fasiComm->first();
    $dataReg = $prima->data_registrazione;
    $vecchia = $dataReg && $dataReg < '2026-03-25' ? ' *** VECCHIA ***' : '';

    echo "--- {$comm} | DataReg:{$dataReg} | {$prima->cliente_nome}{$vecchia} ---\n";
    foreach ($fasiComm as $f) {
        echo "  #{$f->id} | {$f->fase} | stato:{$f->stato} | creata:{$f->created_at}\n";
    }
    echo "\n";
}

// Conta fasi a stato 0 create oggi su commesse con tutte le altre fasi >= 3
echo "\n=== FASI A STATO 0 SU COMMESSE GIA' COMPLETATE ===\n\n";
$fasiSospette = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'f.ordine_id', '=', 'o.id')
    ->whereDate('f.created_at', $oggi)
    ->where('f.stato', 0)
    ->whereNull('f.deleted_at')
    ->select('f.id', 'f.fase', 'o.commessa', 'o.cliente_nome', 'o.data_registrazione')
    ->get();

foreach ($fasiSospette as $f) {
    // Controlla se tutte le altre fasi della commessa sono >= 3
    $altroStato = DB::table('ordine_fasi as f2')
        ->join('ordini as o2', 'f2.ordine_id', '=', 'o2.id')
        ->where('o2.commessa', $f->commessa)
        ->where('f2.id', '!=', $f->id)
        ->whereNull('f2.deleted_at')
        ->whereRaw("f2.stato REGEXP '^[0-9]+$' AND f2.stato < 3")
        ->count();

    if ($altroStato == 0) {
        echo "  {$f->commessa} | {$f->fase} | #{$f->id} | DataReg:{$f->data_registrazione} | {$f->cliente_nome} ← COMMESSA COMPLETATA!\n";
    }
}
