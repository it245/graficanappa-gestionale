<?php
// Ricalcola tutte le commesse con fasi in pausa
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use App\Services\FaseStatoService;

// Trova tutte le fasi in pausa (stato non numerico, non 0-4)
$fasiInPausa = OrdineFase::with('ordine')
    ->where('stato', 'NOT REGEXP', '^[0-4]$')
    ->whereNotNull('stato')
    ->where('stato', '!=', '')
    ->get();

$commesse = $fasiInPausa->pluck('ordine.commessa')->unique()->filter();

echo "Commesse con fasi in pausa: " . $commesse->count() . "\n\n";

foreach ($commesse as $commessa) {
    echo "Ricalcolo $commessa...\n";
    FaseStatoService::ricalcolaCommessa($commessa);

    $fasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
        ->orderBy('priorita')
        ->get();

    foreach ($fasi as $f) {
        $marker = '';
        if (!is_numeric($f->stato)) $marker = ' *** PAUSA';
        echo "  {$f->fase} | Stato: {$f->stato}{$marker}\n";
    }
    echo "\n";
}

echo "Fatto.\n";
