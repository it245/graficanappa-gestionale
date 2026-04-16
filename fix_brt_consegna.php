<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\FaseStatoService;
use App\Models\OrdineFase;
use App\Models\Ordine;

$commesse = [
    '0066186-26',
    '0066736-26',
    '0066146-26',
    '0066187-26',
    '0066508-26',
    '0066588-26',
];

foreach ($commesse as $commessa) {
    $ordineIds = Ordine::where('commessa', $commessa)->pluck('id');
    if ($ordineIds->isEmpty()) {
        echo "  {$commessa} — ordine non trovato\n";
        continue;
    }

    // Trova BRT e metti a stato 4
    $brt = OrdineFase::whereIn('ordine_id', $ordineIds)
        ->whereIn('fase', ['BRT1', 'brt1', 'BRT'])
        ->where('stato', 3)
        ->get();

    if ($brt->isEmpty()) {
        echo "  {$commessa} — BRT non trovato a stato 3\n";
        continue;
    }

    foreach ($brt as $fase) {
        $fase->stato = 4;
        $fase->data_fine = $fase->data_fine ?: now()->format('Y-m-d H:i:s');
        $fase->save();
    }

    // Propaga consegnato a tutta la commessa
    FaseStatoService::propagaConsegnato($commessa);

    echo "  {$commessa} — BRT→4, propagato consegnato\n";
}

echo "\nDone.\n";
