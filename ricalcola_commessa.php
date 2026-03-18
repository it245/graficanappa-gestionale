<?php
// Uso: php ricalcola_commessa.php 66760
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cerca = $argv[1] ?? '66760';
$commessa = '00' . $cerca . '-26';

echo "Ricalcolo stati per $commessa...\n";
App\Services\FaseStatoService::ricalcolaCommessa($commessa);
echo "Fatto.\n";

// Verifica
$fasi = App\Models\OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
    ->with('faseCatalogo.reparto')
    ->orderBy('priorita')
    ->get();

foreach ($fasi as $f) {
    $reparto = $f->faseCatalogo->reparto->nome ?? '-';
    echo "  {$f->fase} | {$reparto} | Stato: {$f->stato}\n";
}
