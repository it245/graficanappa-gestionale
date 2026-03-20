<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;

$commesse = ['0066513-26', '0066516-26', '0066654-26', '0066736-26', '0066760-26', '0066833-26'];

foreach ($commesse as $c) {
    $fasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $c))
        ->where(function ($q) {
            $q->where('fase', 'LIKE', 'STAMPAXL106%')
              ->orWhere('fase', 'STAMPA')
              ->orWhere('fase', 'LIKE', 'STAMPA XL%');
        })
        ->where('stato', 3)
        ->get();

    foreach ($fasi as $f) {
        $f->stato = 0;
        $f->data_fine = null;
        $f->fogli_buoni = 0;
        $f->fogli_scarto = 0;
        $f->qta_prod = 0;
        $f->save();
        echo "RIPRISTINATA: {$c} | {$f->fase} → stato 0" . PHP_EOL;
    }

    \App\Services\FaseStatoService::ricalcolaCommessa($c);
    echo "  Ricalcolati stati {$c}" . PHP_EOL;
}

echo PHP_EOL . "DONE — 6 commesse ripristinate" . PHP_EOL;
