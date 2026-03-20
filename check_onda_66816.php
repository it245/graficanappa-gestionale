<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = '0066816-26';

echo "=== ONDA: Commessa {$commessa} ===" . PHP_EOL . PHP_EOL;

// Documenti
$docs = DB::connection('onda')->select("
    SELECT t.IdDoc, t.CodCommessa, t.DataRegistrazione, t.TipoDocumento
    FROM ATTDocTeste t WHERE t.CodCommessa = ?
", [$commessa]);

echo "Documenti ATT: " . count($docs) . PHP_EOL;
foreach ($docs as $d) {
    echo "  IdDoc:{$d->IdDoc} | Tipo:{$d->TipoDocumento} | Data:{$d->DataRegistrazione}" . PHP_EOL;
}

// Ordini produzione
$prd = DB::connection('onda')->select("
    SELECT p.IdDoc, p.CodCommessa, p.CodArt, p.OC_Descrizione, p.QtaDaProdurre
    FROM PRDDocTeste p WHERE p.CodCommessa = ?
", [$commessa]);

echo PHP_EOL . "Ordini PRD: " . count($prd) . PHP_EOL;
foreach ($prd as $p) {
    echo "  IdDoc:{$p->IdDoc} | Art:{$p->CodArt} | Desc:" . substr($p->OC_Descrizione ?? '', 0, 60) . " | Qta:{$p->QtaDaProdurre}" . PHP_EOL;

    // Fasi per questo ordine
    $fasi = DB::connection('onda')->select("
        SELECT f.CodFase, f.CodMacchina, f.QtaDaLavorare, f.CodUnMis
        FROM PRDDocFasi f WHERE f.IdDoc = ?
    ", [$p->IdDoc]);

    echo "  Fasi:" . PHP_EOL;
    foreach ($fasi as $f) {
        echo "    {$f->CodFase} | Macchina:{$f->CodMacchina} | Qta:{$f->QtaDaLavorare} | UM:{$f->CodUnMis}" . PHP_EOL;
    }
}

echo PHP_EOL . "=== MES: Commessa {$commessa} ===" . PHP_EOL;
$fasiMes = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->where('ordini.commessa', $commessa)
    ->select('ordine_fasi.fase', 'ordine_fasi.stato', 'ordine_fasi.esterno',
             'ordine_fasi.fase_catalogo_id', 'ordini.descrizione')
    ->get();

echo "Fasi MES: " . $fasiMes->count() . PHP_EOL;
foreach ($fasiMes as $f) {
    echo "  {$f->fase} | stato:{$f->stato} | esterno:" . ($f->esterno ? 'SI' : 'NO') . " | cat_id:{$f->fase_catalogo_id}" . PHP_EOL;
    echo "    Desc: " . substr($f->descrizione ?? '', 0, 60) . PHP_EOL;
}
