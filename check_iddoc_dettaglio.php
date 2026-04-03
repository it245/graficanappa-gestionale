<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = $argv[1] ?? '0066548-26';

$docs = DB::connection('onda')->select("
    SELECT p.IdDoc, p.CodArt, p.OC_Descrizione, p.QtaDaProdurre, p.CodCarta, p.QtaCarta
    FROM PRDDocTeste p
    WHERE p.CodCommessa = ?
    ORDER BY p.IdDoc
", [$commessa]);

echo "=== DOCUMENTI ONDA per {$commessa} ===\n\n";
foreach ($docs as $d) {
    echo "IdDoc: {$d->IdDoc}\n";
    echo "  CodArt: {$d->CodArt}\n";
    echo "  Descrizione: {$d->OC_Descrizione}\n";
    echo "  QtaDaProdurre: {$d->QtaDaProdurre}\n";
    echo "  CodCarta: {$d->CodCarta}\n";
    echo "  QtaCarta: {$d->QtaCarta}\n\n";
}
