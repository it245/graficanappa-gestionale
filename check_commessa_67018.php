<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== MES: ORDINI COMMESSA 0067018-26 ===\n";
$ordini = App\Models\Ordine::where('commessa', '0067018-26')->with('fasi')->get();
foreach ($ordini as $o) {
    echo "  ID={$o->id} desc=[{$o->descrizione}] qta={$o->qta_richiesta} cod_art={$o->cod_art}\n";
    foreach ($o->fasi as $f) {
        echo "    Fase: {$f->fase} stato={$f->stato} qta_carta={$f->qta_carta}\n";
    }
}

echo "\n=== ONDA: DOCUMENTI PRODUZIONE 67018 ===\n";
$righe = DB::connection('onda')->select("
    SELECT p.IdDoc, p.CodCommessa, p.CodArt, p.OC_Descrizione,
           f.CodFase, f.QtaDaLavorare, f.CodUnMis
    FROM PRDDocTeste p
    LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
    WHERE p.CodCommessa LIKE '%67018%'
    ORDER BY p.IdDoc, f.CodFase
");
$currentDoc = null;
foreach ($righe as $r) {
    if ($r->IdDoc !== $currentDoc) {
        $currentDoc = $r->IdDoc;
        echo "\n  IdDoc={$r->IdDoc} Commessa={$r->CodCommessa} Art=[{$r->CodArt}] Desc=[{$r->OC_Descrizione}]\n";
    }
    echo "    Fase: {$r->CodFase} Qta={$r->QtaDaLavorare} UM={$r->CodUnMis}\n";
}
