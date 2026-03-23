<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use App\Models\Ordine;

$commesse = ['0066514-26', '0066515-26'];

foreach ($commesse as $commessa) {
    echo "=== {$commessa} ===" . PHP_EOL;

    // Trova ordini — quello creato oggi (più recente) è il duplicato
    $ordini = Ordine::where('commessa', $commessa)->orderBy('id')->get();

    echo "  Ordini: " . $ordini->count() . PHP_EOL;
    foreach ($ordini as $o) {
        $fasiCount = OrdineFase::where('ordine_id', $o->id)->count();
        echo "  ID:{$o->id} | Art:{$o->cod_art} | created:" . ($o->created_at ?? '-') . " | fasi:{$fasiCount}" . PHP_EOL;
    }

    // Se ci sono 2+ ordini con lo stesso cod_art, elimina il più recente
    $perCodArt = $ordini->groupBy('cod_art');
    foreach ($perCodArt as $codArt => $gruppo) {
        if ($gruppo->count() <= 1) continue;

        // Tieni il primo (più vecchio), elimina gli altri
        $daTenere = $gruppo->first();
        $daEliminare = $gruppo->skip(1);

        foreach ($daEliminare as $dup) {
            $fasiDup = OrdineFase::where('ordine_id', $dup->id)->get();
            echo "  ELIMINA ordine ID:{$dup->id} ({$codArt}) con {$fasiDup->count()} fasi" . PHP_EOL;
            foreach ($fasiDup as $f) {
                echo "    - {$f->fase} | stato:{$f->stato}" . PHP_EOL;
                $f->delete();
            }
            $dup->delete();
        }
    }

    \App\Services\FaseStatoService::ricalcolaCommessa($commessa);
    echo PHP_EOL;
}
echo "DONE" . PHP_EOL;
