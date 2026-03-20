<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;

$commesse = ['0066800-26', '0066802-26', '0066807-26', '0066821-26'];

foreach ($commesse as $c) {
    $fasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $c))
        ->where(function ($q) {
            $q->where('fase', 'LIKE', 'STAMPAXL106%')
              ->orWhere('fase', 'LIKE', 'STAMPA XL%');
        })
        ->where('stato', 3)
        ->where('data_fine', '>=', '2026-03-19 12:35:00')
        ->where('data_fine', '<=', '2026-03-19 12:37:00')
        ->get();

    foreach ($fasi as $f) {
        $f->stato = 0;
        $f->data_fine = null;
        $f->fogli_buoni = 0;
        $f->fogli_scarto = 0;
        $f->qta_prod = 0;
        $f->save();
        echo "RIPRISTINATA: {$c} | {$f->fase} → stato 0, data_fine NULL, fogli reset" . PHP_EOL;
    }

    // Ricalcola stati commessa
    $primoOrdine = \App\Models\Ordine::where('commessa', $c)->first();
    if ($primoOrdine) {
        \App\Services\FaseStatoService::ricalcolaCommessa($c);
        echo "  Ricalcolati stati per {$c}" . PHP_EOL;
    }
}

echo PHP_EOL . "DONE" . PHP_EOL;
