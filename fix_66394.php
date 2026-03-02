<?php
// Cleanup: rimuove BRT1 duplicati dalla commessa 0066394-26
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Services\FaseStatoService;

$ordini = Ordine::where('commessa', '0066394-26')->get();
$rimossi = 0;

foreach ($ordini as $ordine) {
    $brtFasi = OrdineFase::where('ordine_id', $ordine->id)
        ->where('fase', 'BRT1')
        ->orderBy('id')
        ->get();

    // Tieni la prima, elimina le altre
    if ($brtFasi->count() > 1) {
        foreach ($brtFasi->skip(1) as $dup) {
            $dup->operatori()->detach();
            $dup->delete();
            $rimossi++;
            echo "Rimosso BRT1 duplicato #{$dup->id} da ordine #{$ordine->id} ({$ordine->descrizione})\n";
        }
    }

    FaseStatoService::ricalcolaStati($ordine->id);
}

echo "\nRimossi $rimossi BRT1 duplicati\n";
