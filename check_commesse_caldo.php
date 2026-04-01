<?php
// Estrae tutte le commesse con STAMPACALDOJOH e le loro descrizioni
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$fasi = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->where('ordine_fasi.fase', 'LIKE', '%STAMPACALDO%')
    ->whereNull('ordine_fasi.deleted_at')
    ->select('ordini.commessa', 'ordini.cod_art', 'ordini.descrizione', 'ordini.cliente_nome', 'ordine_fasi.stato')
    ->orderBy('ordini.commessa')
    ->get();

echo "=== COMMESSE CON STAMPA A CALDO ({$fasi->count()}) ===\n\n";
echo str_pad('COMMESSA', 15) . str_pad('STATO', 7) . str_pad('COD_ART', 30) . str_pad('CLIENTE', 30) . 'DESCRIZIONE' . "\n";
echo str_repeat('-', 150) . "\n";

foreach ($fasi as $f) {
    echo str_pad($f->commessa, 15)
       . str_pad($f->stato, 7)
       . str_pad(substr($f->cod_art ?? '-', 0, 28), 30)
       . str_pad(substr($f->cliente_nome ?? '-', 0, 28), 30)
       . substr($f->descrizione ?? '-', 0, 80)
       . "\n";
}
