<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;

$commessa = '0066856-26';

echo "=== FIX 66856 ===" . PHP_EOL;

// Elimina il duplicato BROSSFRESATA/A4EST (rinominato dal fix precedente)
// Teniamo EXTBROSSFRESATA/A4EST che è il nome originale in Onda
$dup = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
    ->where('fase', 'BROSSFRESATA/A4EST')
    ->get();
foreach ($dup as $f) {
    echo "ELIMINA DUP: {$f->fase} (ID:{$f->id}) — teniamo EXTBROSSFRESATA/A4EST" . PHP_EOL;
    $f->delete();
}

\App\Services\FaseStatoService::ricalcolaCommessa($commessa);
echo PHP_EOL . "DONE" . PHP_EOL;
echo "Ora lancia: php artisan onda:sync 0066856-26" . PHP_EOL;
