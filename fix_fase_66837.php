<?php
// Reset operatore e data_inizio sulla fase "stampa xl" della commessa 66837
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = '0066837-26';

$fase = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'f.ordine_id', '=', 'o.id')
    ->where('o.commessa', $commessa)
    ->whereRaw("LOWER(f.fase) LIKE '%stampa%xl%'")
    ->select('f.id', 'f.fase', 'f.stato', 'f.data_inizio')
    ->first();

if (!$fase) {
    echo "Fase stampa xl non trovata per {$commessa}\n";
    exit(1);
}

echo "Trovata fase #{$fase->id}: {$fase->fase} | stato:{$fase->stato} | data_inizio:{$fase->data_inizio}\n";

// Reset data_inizio e stato a 0
DB::table('ordine_fasi')->where('id', $fase->id)->update([
    'data_inizio' => null,
    'stato' => 0,
    'qta_prod' => 0,
]);

// Rimuovi operatori assegnati
$rimossi = DB::table('fase_operatore')->where('fase_id', $fase->id)->delete();

echo "Fatto: data_inizio=NULL, stato=0, qta_prod=0, operatori rimossi: {$rimossi}\n";
