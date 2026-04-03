<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$fasi = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
    ->join('reparti', 'fasi_catalogo.reparto_id', '=', 'reparti.id')
    ->whereIn('reparti.nome', ['digitale', 'finitura digitale'])
    ->where('ordine_fasi.stato', 2)
    ->whereNull('ordine_fasi.deleted_at')
    ->select(
        'ordine_fasi.id', 'ordine_fasi.fase', 'ordine_fasi.stato',
        'ordine_fasi.data_inizio', 'ordine_fasi.data_fine',
        'ordine_fasi.qta_fase', 'ordine_fasi.qta_prod',
        'ordini.commessa', 'ordini.cliente_nome', 'ordini.descrizione',
        'reparti.nome as reparto'
    )
    ->orderBy('ordine_fasi.data_inizio')
    ->get();

echo "=== FASI DIGITALE A STATO 2 (AVVIATO) — {$fasi->count()} ===\n\n";

foreach ($fasi as $f) {
    $giorni = $f->data_inizio ? now()->diffInDays(\Carbon\Carbon::parse($f->data_inizio)) : '?';
    echo "ID:{$f->id} | {$f->commessa} | {$f->fase} ({$f->reparto})\n";
    echo "  Cliente: {$f->cliente_nome}\n";
    echo "  Desc: " . substr($f->descrizione ?? '-', 0, 60) . "\n";
    echo "  Qta: {$f->qta_fase} | Prod: {$f->qta_prod}\n";
    echo "  Inizio: {$f->data_inizio} | Fine: " . ($f->data_fine ?? '-') . "\n";
    echo "  Aperta da: {$giorni} giorni\n\n";
}

// Riepilogo
$vecchie = $fasi->filter(function($f) {
    return $f->data_inizio && now()->diffInDays(\Carbon\Carbon::parse($f->data_inizio)) > 3;
});
echo "=== RIEPILOGO ===\n";
echo "Totale aperte: {$fasi->count()}\n";
echo "Aperte da piu di 3 giorni: {$vecchie->count()}\n";
