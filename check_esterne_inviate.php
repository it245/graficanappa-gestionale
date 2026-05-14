<?php
/**
 * Confronta lavorazioni esterne dichiarate dal bot vs MES reale.
 * Estrae tutte le fasi esterne stato>=2 (inviate, lavorate, terminate).
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('ordine_fasi as orf')
    ->join('ordini as o', 'o.id', '=', 'orf.ordine_id')
    ->where('orf.stato', '5')
    ->whereNull('orf.deleted_at')
    ->select('o.commessa', 'orf.fase', 'o.descrizione', 'o.cliente_nome')
    ->orderBy('o.commessa')
    ->get();

echo "Totale fasi a stato 5 (EXT inviato): " . count($rows) . "\n\n";

foreach ($rows as $r) {
    echo "{$r->commessa} | {$r->fase} | " . substr($r->descrizione ?? '', 0, 70) . " | " . substr($r->cliente_nome ?? '', 0, 30) . "\n";
}
