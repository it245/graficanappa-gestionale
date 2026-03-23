<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;

$commessa = '0066856-26';

echo "=== FIX 66856 ===" . PHP_EOL;

// 1. Elimina duplicato EXTBROSSFRESATA/A4EST (la versione rinominata BROSSFRESATA/A4EST esiste già)
$extDup = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
    ->where('fase', 'EXTBROSSFRESATA/A4EST')
    ->get();
foreach ($extDup as $f) {
    echo "ELIMINA DUP: {$f->fase} (ID:{$f->id})" . PHP_EOL;
    $f->delete();
}

// 2. Marca come esterne (TipoRiga=2 in Onda)
$fasiEsterne = ['BROSSFRESATA/A4EST', 'BROSSCOPEST'];
foreach ($fasiEsterne as $nome) {
    $fase = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
        ->where('fase', $nome)
        ->first();
    if ($fase && !$fase->esterno) {
        $fase->esterno = 1;
        $fase->save();
        echo "ESTERNO: {$nome} (ID:{$fase->id}) → esterno:SI" . PHP_EOL;
    }
}

// 3. Sincronizza per creare la STAMPA mancante (rivista)
echo PHP_EOL . "Rilancia sync per creare fasi mancanti:" . PHP_EOL;
echo "  php artisan onda:sync 0066856-26" . PHP_EOL;

\App\Services\FaseStatoService::ricalcolaCommessa($commessa);
echo PHP_EOL . "DONE" . PHP_EOL;
