<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Fix: aggiorna descrizioni da Onda
$fix6380 = App\Models\Ordine::find(6380);
if ($fix6380 && str_contains($fix6380->descrizione, 'PISTACCHIO')) {
    $fix6380->descrizione = 'AST. 500 GR AMOR GOLOSO MIX (P01284)';
    $fix6380->save();
    echo "FIX: Ordine 6380 descrizione → MIX\n";
}
$fix6379 = App\Models\Ordine::find(6379);
if ($fix6379 && str_contains($fix6379->descrizione, 'PISTACCHIO')) {
    $fix6379->descrizione = 'AST. 500 GR AMOR GOLOSO MIX (P01284)';
    $fix6379->save();
    echo "FIX: Ordine 6379 descrizione → MIX\n";
}
echo "\n";

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
