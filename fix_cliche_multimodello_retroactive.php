<?php
/**
 * Fix retroactive cliché su commesse multi-modello.
 * Identifica ordini cloned con cliché ereditato dal template (duplicato su >1 ordine
 * stessa commessa) e resetta + re-matcha individualmente per descrizione.
 *
 * Fix di ieri (reset cliché su clone) vale per NUOVI cloni. Questo script
 * sistema gli ESISTENTI già clonati prima del fix.
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Ordine;
use App\Services\ClicheMatchService;

// Trova commesse con >1 ordine multi-modello stessa commessa
// dove cliche_numero è uguale (= duplicato sospetto da clone)
$duplicate = DB::table('ordini')
    ->select('commessa', 'cliche_numero', DB::raw('COUNT(*) as n'))
    ->whereNotNull('cliche_numero')
    ->whereNull('deleted_at')
    ->groupBy('commessa', 'cliche_numero')
    ->having('n', '>', 1)
    ->get();

echo "Commesse con cliché duplicato: " . $duplicate->count() . "\n\n";

$resetCount = 0;
foreach ($duplicate as $d) {
    $ids = Ordine::where('commessa', $d->commessa)
        ->where('cliche_numero', $d->cliche_numero)
        ->whereNull('deleted_at')
        ->pluck('id', 'descrizione');

    echo "Commessa {$d->commessa} cliché {$d->cliche_numero}: " . count($ids) . " ordini\n";
    foreach ($ids as $desc => $id) {
        echo "  - $desc (id $id)\n";
    }

    // Reset cliché su tutti (saranno re-matchati dopo)
    Ordine::where('commessa', $d->commessa)
        ->where('cliche_numero', $d->cliche_numero)
        ->whereNull('deleted_at')
        ->where(fn($q) => $q->whereNull('cliche_match_type')->orWhere('cliche_match_type', 'auto'))
        ->update([
            'cliche_numero' => null,
            'cliche_match_type' => null,
            'cliche_matched_at' => null,
        ]);
    $resetCount += count($ids);
}

echo "\nResettati: $resetCount ordini.\n";
echo "\nLancio re-match automatico per descrizione...\n";

$result = ClicheMatchService::matchAll();
echo "Re-match: matched={$result['matched']} updated={$result['updated']}\n";
