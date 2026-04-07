<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

error_reporting(E_ALL & ~E_DEPRECATED);

$ddts = App\Models\DdtSpedizione::select('numero_ddt')->distinct()->get();

echo "=== Generazione PDF DDT mancanti ===\n";
$generati = 0;

foreach ($ddts as $d) {
    if (!$d->numero_ddt) continue;
    echo "DDT {$d->numero_ddt}: ";
    try {
        $path = App\Services\DdtPdfService::generaESalva($d->numero_ddt);
        if ($path) {
            echo "OK → $path\n";
            $generati++;
        } else {
            echo "NON TROVATO in Onda\n";
        }
    } catch (Exception $e) {
        echo "ERRORE: " . $e->getMessage() . "\n";
    }
}

echo "\nGenerati: $generati PDF\n";
