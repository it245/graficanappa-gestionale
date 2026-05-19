<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$commesse = ['0066956-26', '0067343-26'];

foreach ($commesse as $c) {
    echo "\n=== {$c} ===\n";
    $shortC = ltrim(explode('-', $c)[0], '0');

    echo "\n>>> Ordini MES:\n";
    $ordini = DB::table('ordini')->where('commessa', $c)->get();
    foreach ($ordini as $o) {
        echo "  id={$o->id} | cod_art={$o->cod_art} | desc=" . substr($o->descrizione ?? '', 0, 60) . "\n";
    }

    echo "\n>>> Fasi MES (tutte):\n";
    $fasi = DB::table('ordine_fasi as f')->join('ordini as o', 'o.id', 'f.ordine_id')
        ->where('o.commessa', $c)
        ->select('f.fase', 'f.stato', 'o.descrizione', 'f.id', 'f.data_inizio')
        ->orderBy('f.id')->get();
    foreach ($fasi as $f) {
        echo "  id={$f->id} | {$f->fase} | stato={$f->stato} | " . substr($f->descrizione ?? '', 0, 40) . "\n";
    }

    echo "\n>>> Onda — ordini PRD (produzione) per commessa {$shortC}:\n";
    try {
        $ondaOrdini = DB::connection('onda')->select("
            SELECT op.CodCommessa, op.NumOrdProduzione, op.CodArticolo, op.Descrizione
            FROM ATTOrdiniProduzioneTeste op
            WHERE op.CodCommessa LIKE ?
        ", ['%'.$shortC.'%']);
        foreach ($ondaOrdini as $o) {
            echo "  {$o->CodCommessa} | OdP={$o->NumOrdProduzione} | art={$o->CodArticolo} | " . substr($o->Descrizione ?? '', 0, 50) . "\n";
        }
        if (empty($ondaOrdini)) echo "  (nessun OdP trovato)\n";
    } catch (\Throwable $e) {
        echo "  ERR: " . $e->getMessage() . "\n";
    }

    echo "\n>>> Onda — fasi ciclo PRD:\n";
    try {
        $ondaFasi = DB::connection('onda')->select("
            SELECT opf.NumOrdProduzione, opf.CodFase, opf.Descrizione, opf.Sequenza
            FROM ATTOrdiniProduzioneFasi opf
            JOIN ATTOrdiniProduzioneTeste op ON opf.NumOrdProduzione = op.NumOrdProduzione
            WHERE op.CodCommessa LIKE ?
            ORDER BY opf.NumOrdProduzione, opf.Sequenza
        ", ['%'.$shortC.'%']);
        foreach ($ondaFasi as $f) {
            echo "  OdP={$f->NumOrdProduzione} | seq={$f->Sequenza} | {$f->CodFase} | " . substr($f->Descrizione ?? '', 0, 40) . "\n";
        }
        if (empty($ondaFasi)) echo "  (nessuna fase trovata)\n";
    } catch (\Throwable $e) {
        echo "  ERR: " . $e->getMessage() . "\n";
    }
}
