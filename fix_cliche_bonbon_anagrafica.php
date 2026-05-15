<?php
/**
 * Fix anagrafica cliché 2203/2204: rimuove "CIO CIO" errore di battitura.
 * Reale: "BON BON CREAM" / "BON BON CREAM SFUMATO".
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\ClicheMatchService;

DB::table('cliche_anagrafica')
    ->where('numero', 2203)
    ->update(['descrizione_raw' => 'BON BON CREAM', 'updated_at' => now()]);
echo "2203 aggiornato: BON BON CREAM\n";

DB::table('cliche_anagrafica')
    ->where('numero', 2204)
    ->update(['descrizione_raw' => 'BON BON CREAM SFUMATO', 'updated_at' => now()]);
echo "2204 aggiornato: BON BON CREAM SFUMATO\n";

echo "\nLancio re-match...\n";
$r = ClicheMatchService::matchAll();
echo "Re-match: matched={$r['matched']} updated={$r['updated']}\n";
