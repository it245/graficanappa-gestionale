<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;

// Trova le 2 PLAOPA1LATO della 66783
$fasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', '0066783-26'))
    ->where('fase', 'PLAOPA1LATO')
    ->with('faseCatalogo.reparto')
    ->get();

echo "PLAOPA1LATO nella 66783: " . $fasi->count() . "\n";
foreach ($fasi as $f) {
    $rep = $f->faseCatalogo->reparto->nome ?? '?';
    echo "  ID:{$f->id} | Reparto: {$rep} | Stato: {$f->stato} | Esterno: " . ($f->esterno ? 'SI' : 'no') . " | CatID: {$f->fase_catalogo_id}\n";
}

// Elimina quella nel reparto "esterno" (duplicata dallo script DDT)
$repartoEsterno = App\Models\Reparto::where('nome', 'esterno')->first();
if ($repartoEsterno) {
    $dupEsterna = $fasi->filter(fn($f) => ($f->faseCatalogo->reparto_id ?? null) == $repartoEsterno->id);
    foreach ($dupEsterna as $f) {
        echo "\nElimino duplicata ID:{$f->id} (reparto esterno)\n";
        $f->delete();
    }
}

// Marca la rimasta come esterna (se ha ddt_fornitore_id)
$rimasta = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', '0066783-26'))
    ->where('fase', 'PLAOPA1LATO')
    ->first();

if ($rimasta && !$rimasta->esterno) {
    $rimasta->esterno = 1;
    $rimasta->stato = 2;
    $rimasta->note = 'Inviato a: KRESIA SRL';
    $rimasta->save();
    echo "Fase ID:{$rimasta->id} marcata come esterna\n";
} elseif ($rimasta) {
    echo "Fase ID:{$rimasta->id} già esterna\n";
}

echo "\nFatto.\n";
