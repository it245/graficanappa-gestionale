<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;

// Fix EXTALLEST.SHOPPER024 duplicati nella 66692
$fasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', '0066692-26'))
    ->where('fase', 'EXTALLEST.SHOPPER024')
    ->orderBy('id')
    ->get();

echo "EXTALLEST.SHOPPER024 nella 66692: " . $fasi->count() . "\n";

if ($fasi->count() > 1) {
    $keep = $fasi->first();
    echo "Tengo ID:{$keep->id}\n";
    $duplicati = $fasi->slice(1);
    foreach ($duplicati as $dup) {
        echo "Elimino ID:{$dup->id}\n";
        $dup->delete();
    }
}

// Fix anche PLAOPA1LATO duplicati - tieni solo quelle terminate + 1 pronta
$plaopa = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', '0066692-26'))
    ->where('fase', 'PLAOPA1LATO')
    ->orderBy('id')
    ->get();

echo "\nPLAOPA1LATO nella 66692: " . $plaopa->count() . "\n";
foreach ($plaopa as $p) {
    echo "  ID:{$p->id} | Stato: {$p->stato} | Qta: {$p->qta_fase}\n";
}

echo "\nFatto.\n";
