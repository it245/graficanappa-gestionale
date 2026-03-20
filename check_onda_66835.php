<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = '0066835-26';

echo "=== ONDA: {$commessa} ===" . PHP_EOL;

$prd = DB::connection('onda')->select("
    SELECT p.IdDoc, p.CodArt, p.OC_Descrizione, p.QtaDaProdurre
    FROM PRDDocTeste p WHERE p.CodCommessa = ?
", [$commessa]);

foreach ($prd as $p) {
    echo PHP_EOL . "Ordine PRD {$p->IdDoc} | Art:{$p->CodArt} | Desc:" . substr($p->OC_Descrizione ?? '', 0, 60) . PHP_EOL;

    $fasi = DB::connection('onda')->select("SELECT f.Sequenza, f.CodFase, f.CodMacchina, f.QtaDaLavorare, f.CodUnMis FROM PRDDocFasi f WHERE f.IdDoc = ? ORDER BY f.Sequenza", [$p->IdDoc]);
    foreach ($fasi as $f) {
        $ext = str_starts_with(strtoupper($f->CodFase), 'EXT') ? ' ← EXT!' : '';
        echo "  Seq:{$f->Sequenza} | {$f->CodFase} | qta:{$f->QtaDaLavorare} {$f->CodUnMis}{$ext}" . PHP_EOL;
    }
}

$righe = DB::connection('onda')->select("
    SELECT r.NrRiga, r.CodArt, r.Descrizione, r.Qta, r.CodUnMis
    FROM ATTDocRighe r JOIN ATTDocTeste t ON r.IdDoc = t.IdDoc
    WHERE t.CodCommessa = ? ORDER BY r.NrRiga
", [$commessa]);

echo PHP_EOL . "ATTDocRighe:" . PHP_EOL;
foreach ($righe as $r) {
    echo "  Riga {$r->NrRiga} | {$r->CodArt} | " . substr($r->Descrizione ?? '', 0, 50) . " | qta:{$r->Qta} {$r->CodUnMis}" . PHP_EOL;
}

echo PHP_EOL . "=== MES ===" . PHP_EOL;
$fasi = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->where('ordini.commessa', $commessa)
    ->select('ordine_fasi.fase', 'ordine_fasi.stato', 'ordine_fasi.esterno', 'ordini.descrizione')
    ->get();
foreach ($fasi as $f) {
    echo "  {$f->fase} | stato:{$f->stato} | esterno:" . ($f->esterno ? 'SI' : 'NO') . " | " . substr($f->descrizione ?? '', 0, 40) . PHP_EOL;
}
