<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$commesse = ['0066839-26', '0066801-26'];

foreach ($commesse as $commessa) {
    echo "=== {$commessa} ===" . PHP_EOL;

    $prd = DB::connection('onda')->select("
        SELECT p.IdDoc, p.CodArt, p.OC_Descrizione
        FROM PRDDocTeste p WHERE p.CodCommessa = ?
    ", [$commessa]);

    foreach ($prd as $p) {
        echo "  PRD {$p->IdDoc} | {$p->CodArt} | " . substr($p->OC_Descrizione ?? '', 0, 50) . PHP_EOL;
        $fasi = DB::connection('onda')->select("
            SELECT f.Sequenza, f.CodFase, f.TipoRiga, f.QtaDaLavorare, f.CodUnMis
            FROM PRDDocFasi f WHERE f.IdDoc = ? ORDER BY f.Sequenza
        ", [$p->IdDoc]);
        foreach ($fasi as $f) {
            $tipo = $f->TipoRiga == 2 ? ' [ESTERNA]' : '';
            echo "    Seq:{$f->Sequenza} | {$f->CodFase} | qta:{$f->QtaDaLavorare} {$f->CodUnMis}{$tipo}" . PHP_EOL;
        }
    }

    echo "  MES:" . PHP_EOL;
    $mes = DB::table('ordine_fasi')
        ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
        ->where('ordini.commessa', $commessa)
        ->select('ordine_fasi.fase', 'ordine_fasi.stato', 'ordine_fasi.esterno')
        ->get();
    foreach ($mes as $f) {
        echo "    {$f->fase} | stato:{$f->stato} | est:" . ($f->esterno ? 'SI' : 'NO') . PHP_EOL;
    }
    echo PHP_EOL;
}
