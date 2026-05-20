<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$comm = '0067502-26';
echo "=== MES commessa $comm ===\n\n";

$ordini = App\Models\Ordine::with(['fasi.faseCatalogo.reparto'])->where('commessa', $comm)->get();
echo "Trovati " . $ordini->count() . " ordini\n\n";

foreach ($ordini as $ord) {
    echo "ORDINE id={$ord->id}\n";
    echo "  cod_articolo={$ord->cod_articolo}\n";
    echo "  descrizione=" . mb_substr($ord->descrizione, 0, 80) . "\n";
    echo "  qta=" . $ord->qta . " UM=" . $ord->um . "\n";
    echo "  Fasi (" . $ord->fasi->count() . "):\n";
    foreach ($ord->fasi as $f) {
        $rep = $f->faseCatalogo->reparto->nome ?? '?';
        $cod = $f->faseCatalogo->fase ?? '?';
        echo sprintf("    %-25s rep=%-15s qta_carta=%-6s stato=%-4s prio=%s\n",
            $cod, $rep, $f->qta_carta ?? '-', $f->stato, $f->priorita ?? '-');
    }
    echo "\n";
}
