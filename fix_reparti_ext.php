<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use App\Models\Reparto;

$correzioni = [
    'Allest.Manuale' => 'legatoria',
    'Allest.Manuale0,2' => 'legatoria',
    'ALLEST.SHOPPER' => 'legatoria',
    'ALLEST.SHOPPER024' => 'legatoria',
    'ALLESTIMENTO.CALENDARI' => 'legatoria',
    'CARTONATO.GEN' => 'legatoria',
];

echo "=== FIX REPARTI: sposta fasi nel reparto corretto ===" . PHP_EOL;

foreach ($correzioni as $faseNome => $repartoNome) {
    $reparto = Reparto::firstOrCreate(['nome' => $repartoNome]);

    // Aggiorna fasi_catalogo
    $catalogo = FasiCatalogo::where('nome', $faseNome)->first();
    if ($catalogo && $catalogo->reparto_id !== $reparto->id) {
        $vecchioReparto = $catalogo->reparto->nome ?? '?';
        $catalogo->reparto_id = $reparto->id;
        $catalogo->save();
        echo "Catalogo: {$faseNome} | {$vecchioReparto} → {$repartoNome}" . PHP_EOL;
    }

    // Aggiorna ordine_fasi che hanno esterno=1 ma ora sono interne
    $fasi = OrdineFase::where('fase', $faseNome)->where('esterno', 1)->get();
    foreach ($fasi as $f) {
        $f->esterno = 0;
        $f->save();
        $commessa = $f->ordine->commessa ?? '?';
        echo "  Fase ID:{$f->id} | {$commessa} | esterno → NO" . PHP_EOL;
    }
}

echo PHP_EOL . "DONE" . PHP_EOL;
