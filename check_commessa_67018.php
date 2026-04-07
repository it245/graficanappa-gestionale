<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$ordini = App\Models\Ordine::where('commessa', '0067018-26')->with('fasi')->get();

echo "=== ORDINI COMMESSA 0067018-26 ===\n";
foreach ($ordini as $o) {
    echo "  ID={$o->id} desc=[{$o->descrizione}] qta={$o->qta_richiesta} cod_art={$o->cod_art}\n";
    foreach ($o->fasi as $f) {
        echo "    Fase: {$f->fase} stato={$f->stato} qta_carta={$f->qta_carta} qta_prod={$f->qta_prod}\n";
    }
}
echo "\nTotale ordini: " . $ordini->count() . "\n";
echo "Totale fasi: " . $ordini->sum(fn($o) => $o->fasi->count()) . "\n";
