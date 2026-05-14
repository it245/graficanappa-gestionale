<?php
/**
 * Verifica fasi SFUST/SFUST.IML.FUSTELLATO a stato 1.
 * Conta per commessa e mostra descrizione varianti.
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->whereIn('ordine_fasi.fase', ['SFUST','SFUST.IML.FUSTELLATO'])
    ->where('ordine_fasi.stato', '1')
    ->whereNull('ordine_fasi.deleted_at')
    ->select('ordini.commessa', 'ordine_fasi.fase', 'ordini.descrizione')
    ->orderBy('ordini.commessa')
    ->get();

$byCommessa = [];
foreach ($rows as $r) {
    $byCommessa[$r->commessa][] = $r->descrizione;
}

echo "Totale fasi SFUST a stato 1: " . count($rows) . "\n";
echo "Commesse distinte: " . count($byCommessa) . "\n\n";
foreach ($byCommessa as $comm => $descs) {
    echo "$comm (" . count($descs) . " varianti):\n";
    foreach ($descs as $d) echo "  - $d\n";
}
