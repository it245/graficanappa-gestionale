<?php
// Corregge fasi EXT → esterno e fasi generico → legatoria
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use App\Models\Reparto;

$repartoEsterno = Reparto::where('nome', 'esterno')->first();
$repartoLegatoria = Reparto::where('nome', 'legatoria')->first();
$repartoGenerico = Reparto::where('nome', 'generico')->first();

if (!$repartoEsterno || !$repartoLegatoria) {
    echo "Reparto esterno o legatoria non trovato!\n";
    exit(1);
}

// 1. Fasi catalogo EXT* → reparto esterno
$fasiExt = FasiCatalogo::where('nome', 'like', 'EXT%')
    ->where('reparto_id', '!=', $repartoEsterno->id)
    ->get();

foreach ($fasiExt as $fc) {
    echo "FasiCatalogo '{$fc->nome}' → esterno\n";
    $fc->update(['reparto_id' => $repartoEsterno->id]);
}
echo "Fasi catalogo EXT corrette: " . $fasiExt->count() . "\n";

// 2. Ordine_fasi EXT* → esterno=true
$updated = OrdineFase::where('fase', 'like', 'EXT%')
    ->where(fn($q) => $q->where('esterno', false)->orWhereNull('esterno'))
    ->update(['esterno' => true]);
echo "Ordine_fasi EXT corrette (esterno=true): $updated\n";

// 3. Fasi catalogo generico → legatoria
if ($repartoGenerico) {
    $fasiGen = FasiCatalogo::where('reparto_id', $repartoGenerico->id)
        ->where('nome', 'not like', 'EXT%')
        ->get();

    foreach ($fasiGen as $fc) {
        echo "FasiCatalogo '{$fc->nome}' generico → legatoria\n";
        $fc->update(['reparto_id' => $repartoLegatoria->id]);
    }
    echo "Fasi catalogo generico→legatoria: " . $fasiGen->count() . "\n";
} else {
    echo "Reparto 'generico' non esiste, nulla da spostare.\n";
}

echo "\nDone.\n";
