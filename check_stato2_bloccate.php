<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$fasi = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
    ->join('reparti', 'fasi_catalogo.reparto_id', '=', 'reparti.id')
    ->where('ordine_fasi.stato', 2)
    ->whereNull('ordine_fasi.deleted_at')
    ->select(
        'ordine_fasi.id', 'ordine_fasi.fase', 'ordine_fasi.stato',
        'ordine_fasi.data_inizio', 'ordine_fasi.data_fine',
        'ordine_fasi.qta_fase', 'ordine_fasi.qta_prod',
        'ordini.commessa', 'ordini.cliente_nome', 'ordini.descrizione',
        'reparti.nome as reparto'
    )
    ->orderBy('reparti.nome')
    ->orderBy('ordine_fasi.data_inizio')
    ->get();

echo "=== TUTTE LE FASI A STATO 2 (AVVIATO) — {$fasi->count()} ===\n\n";

// Raggruppa per reparto
$perReparto = $fasi->groupBy('reparto');

foreach ($perReparto as $reparto => $fasirep) {
    $vecchie = $fasirep->filter(fn($f) => $f->data_inizio && now()->diffInDays(\Carbon\Carbon::parse($f->data_inizio)) > 3);
    echo "--- {$reparto}: {$fasirep->count()} fasi ({$vecchie->count()} aperte da >3gg) ---\n";
    foreach ($fasirep as $f) {
        $giorni = $f->data_inizio ? now()->diffInDays(\Carbon\Carbon::parse($f->data_inizio)) : '?';
        $flag = $giorni > 3 ? ' *** VECCHIA' : '';
        echo "  {$f->commessa} | {$f->fase} | prod:{$f->qta_prod}/{$f->qta_fase} | inizio:{$f->data_inizio} | {$giorni}gg{$flag}\n";
    }
    echo "\n";
}

echo "=== RIEPILOGO ===\n";
echo "Totale fasi a stato 2: {$fasi->count()}\n";
$vecchieTot = $fasi->filter(fn($f) => $f->data_inizio && now()->diffInDays(\Carbon\Carbon::parse($f->data_inizio)) > 3);
echo "Aperte da piu di 3 giorni: {$vecchieTot->count()}\n";
echo "Aperte da piu di 7 giorni: " . $fasi->filter(fn($f) => $f->data_inizio && now()->diffInDays(\Carbon\Carbon::parse($f->data_inizio)) > 7)->count() . "\n";
