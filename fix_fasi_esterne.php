<?php
/**
 * Ripristina le fasi esterne che hanno fase_catalogo_id_originale
 * (cambiate erroneamente a EXT*). Riporta al catalogo originale.
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;

$fasi = OrdineFase::whereNotNull('fase_catalogo_id_originale')
    ->where('fase_catalogo_id', '!=', DB::raw('fase_catalogo_id_originale'))
    ->with('ordine')
    ->get();

echo "Fasi da ripristinare: " . $fasi->count() . "\n\n";

foreach ($fasi as $f) {
    $commessa = $f->ordine->commessa ?? '-';
    echo "  {$commessa} | {$f->fase} (ID:{$f->id}) | Catalogo: {$f->fase_catalogo_id} → {$f->fase_catalogo_id_originale}\n";

    $f->fase_catalogo_id = $f->fase_catalogo_id_originale;
    $f->save();
}

echo "\nFatto.\n";
