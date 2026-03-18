<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Cerca fasi fustella piana con pivot operatore
$fasi = DB::table('ordine_fasi')
    ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
    ->join('reparti', 'fasi_catalogo.reparto_id', '=', 'reparti.id')
    ->where('reparti.nome', 'fustella piana')
    ->where('ordine_fasi.stato', '>=', 2)
    ->whereBetween('ordine_fasi.data_fine', ['2026-03-10', '2026-03-17 23:59:59'])
    ->select('ordine_fasi.id', 'ordine_fasi.fase', 'ordine_fasi.stato', 'ordine_fasi.data_fine')
    ->limit(5)
    ->get();

echo "=== Fasi fustella piana (stato>=2, data_fine 10-17 mar) ===\n";
echo "Trovate: " . $fasi->count() . "\n";
foreach ($fasi as $f) {
    echo "  ID:{$f->id} | {$f->fase} | Stato:{$f->stato} | Fine:{$f->data_fine}\n";

    // Pivot operatore
    $pivot = DB::table('fase_operatore')->where('fase_id', $f->id)->get();
    echo "    Pivot: " . $pivot->count() . " record\n";
    foreach ($pivot as $p) {
        echo "    Op:{$p->operatore_id} | Inizio:{$p->data_inizio} | Fine:{$p->data_fine} | Pausa:{$p->secondi_pausa}\n";
    }
}

// Conta fasi fustella piana SENZA fase_catalogo_id
$senzaCat = DB::table('ordine_fasi')
    ->whereNull('fase_catalogo_id')
    ->where('fase', 'LIKE', 'FUST%')
    ->where('stato', '>=', 2)
    ->count();
echo "\nFasi FUST* senza fase_catalogo_id: $senzaCat\n";

// Fasi fustella con pivot ma senza join al reparto
$pivotFust = DB::table('fase_operatore')
    ->join('ordine_fasi', 'fase_operatore.fase_id', '=', 'ordine_fasi.id')
    ->where('ordine_fasi.fase', 'LIKE', 'FUSTBOBST%')
    ->whereNotNull('fase_operatore.data_fine')
    ->whereBetween('fase_operatore.data_fine', ['2026-03-10', '2026-03-17 23:59:59'])
    ->count();
echo "Pivot fustella BOBST con data_fine nel range: $pivotFust\n";

// Controlla reparto per fasi FUSTBOBST
$repartiFust = DB::table('ordine_fasi')
    ->leftJoin('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
    ->leftJoin('reparti', 'fasi_catalogo.reparto_id', '=', 'reparti.id')
    ->where('ordine_fasi.fase', 'LIKE', 'FUSTBOBST%')
    ->where('ordine_fasi.stato', '>=', 2)
    ->select('reparti.nome as reparto', DB::raw('COUNT(*) as cnt'))
    ->groupBy('reparti.nome')
    ->get();
echo "\nReparti per fasi FUSTBOBST:\n";
foreach ($repartiFust as $r) {
    echo "  " . ($r->reparto ?? 'NULL') . ": {$r->cnt}\n";
}
