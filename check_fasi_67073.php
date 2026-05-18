<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$cod = '0067073-26';

echo "=== ONDA: ATTDocTeste (tutti TipoDocumento) per $cod ===\n";
$att = DB::connection('onda')->select("
    SELECT IdDoc, TipoDocumento, CodCommessa, DataRegistrazione
    FROM ATTDocTeste WHERE CodCommessa = ?
", [$cod]);
foreach ($att as $r) {
    echo "  AttIdDoc={$r->IdDoc} | TipoDoc={$r->TipoDocumento} | Data={$r->DataRegistrazione}\n";
}

echo "\n=== ONDA: PRDDocTeste (tutti PRD) per $cod ===\n";
$prd = DB::connection('onda')->select("
    SELECT IdDoc, CodCommessa, CodArt, OC_Descrizione
    FROM PRDDocTeste WHERE CodCommessa = ?
", [$cod]);
foreach ($prd as $r) {
    echo "  PRD={$r->IdDoc} | CodArt={$r->CodArt} | Desc=" . substr($r->OC_Descrizione, 0, 60) . "\n";
}

echo "\n=== ONDA: PRDDocFasi (tutte fasi da ogni PRD) per $cod ===\n";
$onda = DB::connection('onda')->select("
    SELECT f.CodFase, f.CodMacchina, f.QtaDaLavorare, f.CodUnMis, f.TipoRiga, p.IdDoc
    FROM PRDDocTeste p
    LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
    WHERE p.CodCommessa = ?
    ORDER BY p.IdDoc, f.CodFase
", [$cod]);
foreach ($onda as $r) {
    echo "  PRD={$r->IdDoc} | CodFase={$r->CodFase} | Macchina={$r->CodMacchina} | Qta={$r->QtaDaLavorare}{$r->CodUnMis} | TipoRiga={$r->TipoRiga}\n";
}
echo "Tot Onda fasi: " . count($onda) . "\n";

echo "\n=== ONDA: ATTDocRighe (righe fattura) per $cod ===\n";
$rig = DB::connection('onda')->select("
    SELECT r.CodArt, r.Descrizione, r.TipoRiga, r.NrRiga
    FROM ATTDocTeste t INNER JOIN ATTDocRighe r ON r.IdDoc = t.IdDoc
    WHERE t.CodCommessa = ?
    ORDER BY r.NrRiga
", [$cod]);
foreach ($rig as $r) {
    echo "  Riga {$r->NrRiga} | TipoRiga={$r->TipoRiga} | CodArt={$r->CodArt} | Desc=" . substr($r->Descrizione ?? '', 0, 60) . "\n";
}

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
