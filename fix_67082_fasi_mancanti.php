<?php
/**
 * Aggiunge fasi mancanti (STAMPAINDIGO + BRT1) all'ordine 7882 della commessa 0067082-26.
 * In Onda IdDoc 101897 ha STAMPA+BRT1 ma il dedup sync non le ha copiate.
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use Illuminate\Support\Facades\DB;

$ordineId = 7882;
$commessa = '0067082-26';

$ordine = \App\Models\Ordine::find($ordineId);
if (!$ordine || $ordine->commessa !== $commessa) {
    die("Ordine $ordineId non trovato o commessa sbagliata\n");
}

// Prendi fase_catalogo_id dalla stessa commessa (già presenti nell'altro ordine)
$esistenti = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
    ->whereIn('fase', ['STAMPAINDIGO', 'BRT1'])
    ->get(['fase', 'fase_catalogo_id']);

$mappa = [];
foreach ($esistenti as $f) {
    $mappa[$f->fase] = $f->fase_catalogo_id;
}

$daCreare = [
    ['fase' => 'STAMPAINDIGO', 'qta_fase' => 820, 'um' => 'FG'],
    ['fase' => 'BRT1',         'qta_fase' => 18.18, 'um' => 'KG'],
];

$creati = 0;
foreach ($daCreare as $d) {
    $nome = $d['fase'];
    $already = OrdineFase::where('ordine_id', $ordineId)->where('fase', $nome)->first();
    if ($already) {
        echo "Skip {$nome}: già presente (id={$already->id})\n";
        continue;
    }
    $catId = $mappa[$nome] ?? FasiCatalogo::where('nome', $nome)->value('id');
    if (!$catId) {
        echo "ERRORE: fase_catalogo_id non trovato per {$nome}\n";
        continue;
    }
    $f = OrdineFase::create([
        'ordine_id'        => $ordineId,
        'fase'             => $nome,
        'fase_catalogo_id' => $catId,
        'qta_fase'         => $d['qta_fase'],
        'um'               => $d['um'],
        'priorita'         => 0,
        'stato'            => 0,
        'manuale'          => true,
    ]);
    echo "Creata fase {$nome} (id={$f->id}) per ordine {$ordineId}\n";
    $creati++;
}

echo "\nCreate: {$creati}\n";
