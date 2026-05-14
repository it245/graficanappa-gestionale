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
    ->where('orf.esterno', 1)
    ->whereIn('orf.stato', ['2','3','4','5'])
    ->whereNull('orf.deleted_at')
    ->select('o.commessa', 'orf.fase', 'orf.stato', 'orf.fornitore_esterno', 'o.descrizione', 'o.cliente_nome')
    ->orderBy('o.commessa')
    ->get();

echo "Totale fasi esterne attive (stato 2-5): " . count($rows) . "\n\n";

$byFornitore = [];
foreach ($rows as $r) {
    $f = $r->fornitore_esterno ?: '(nessuno)';
    $byFornitore[$f][] = $r;
}

foreach ($byFornitore as $f => $list) {
    echo "=== $f (" . count($list) . " fasi) ===\n";
    foreach ($list as $r) {
        echo "  {$r->commessa} | {$r->fase} (stato {$r->stato}) | " . substr($r->descrizione ?? '', 0, 60) . "\n";
    }
    echo "\n";
}
