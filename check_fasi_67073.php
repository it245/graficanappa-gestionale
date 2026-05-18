<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$cod = '0067073-26';

echo "=== ONDA: PRDDocFasi per $cod ===\n";
$onda = DB::connection('onda')->select("
    SELECT f.CodFase, f.CodMacchina, f.QtaDaLavorare, f.CodUnMis, f.TipoRiga, p.IdDoc
    FROM PRDDocTeste p
    INNER JOIN ATTDocTeste t ON t.CodCommessa = p.CodCommessa
    LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
    WHERE t.TipoDocumento = '2' AND t.CodCommessa = ?
    ORDER BY f.CodFase
", [$cod]);
foreach ($onda as $r) {
    echo "  PRD={$r->IdDoc} | CodFase={$r->CodFase} | Macchina={$r->CodMacchina} | Qta={$r->QtaDaLavorare}{$r->CodUnMis} | TipoRiga={$r->TipoRiga}\n";
}
echo "Tot Onda: " . count($onda) . "\n";

echo "\n=== MES: ordine_fasi per $cod ===\n";
$ordini = DB::table('ordini')->where('commessa', $cod)->get();
foreach ($ordini as $o) {
    echo "\nOrdine {$o->id} | cod_art={$o->cod_art}\n";
    $fasi = DB::table('ordine_fasi')->where('ordine_id', $o->id)->orderBy('priorita')->get();
    foreach ($fasi as $f) {
        echo "  fase_id={$f->id} | fase={$f->fase} | stato={$f->stato} | prio={$f->priorita} | qta_prod={$f->qta_prod}\n";
    }
    echo "  Tot fasi MES: " . count($fasi) . "\n";
}
