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
    ->select('o.commessa', 'orf.fase', 'orf.note', 'orf.ddt_fornitore_id', 'o.descrizione', 'o.cliente_nome')
    ->orderBy('o.commessa')
    ->get();

echo "Totale fasi a stato 5 (EXT inviato): " . count($rows) . "\n\n";

$byFornitore = [];
foreach ($rows as $r) {
    $fornitore = '(sconosciuto)';
    if ($r->note && preg_match('/Inviato a:\s*(.+?)(?:$|\n)/i', $r->note, $m)) {
        $fornitore = trim($m[1]);
    }
    $byFornitore[$fornitore][] = $r;
}

foreach ($byFornitore as $f => $list) {
    echo "=== $f (" . count($list) . " fasi) ===\n";
    foreach ($list as $r) {
        echo "  {$r->commessa} | {$r->fase} | " . substr($r->descrizione ?? '', 0, 60) . "\n";
    }
    echo "\n";
}
