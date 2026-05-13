<?php
// Uso: php cerca_dettaglio_onda.php 67438
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cerca = $argv[1] ?? '67438';

echo "=== ONDA: Teste ATT + ordini PRD + fasi per commessa $cerca ===\n\n";

$teste = DB::connection('onda')->select(
    "SELECT TOP 5 t.IdDoc, t.CodCommessa, t.DataRegistrazione, t.TipoDocumento
     FROM ATTDocTeste t
     WHERE t.CodCommessa LIKE ?
     ORDER BY t.DataRegistrazione DESC",
    ["%$cerca%"]
);

if (empty($teste)) { echo "Nessuna testa trovata.\n"; exit; }

foreach ($teste as $t) {
    echo "TESTA ATT IdDoc={$t->IdDoc} Commessa={$t->CodCommessa}\n";

    $righeAtt = DB::connection('onda')->select(
        "SELECT NrRiga, TipoRiga, CodArt, Descrizione, Qta
         FROM ATTDocRighe WHERE IdDoc = ? ORDER BY NrRiga",
        [$t->IdDoc]
    );
    echo "  Righe ATT: " . count($righeAtt) . "\n";
    foreach ($righeAtt as $r) {
        echo "    Riga {$r->NrRiga} | Tipo={$r->TipoRiga} | CodArt={$r->CodArt} | Qta={$r->Qta}\n";
        echo "      Desc: " . substr($r->Descrizione ?? '', 0, 100) . "\n";
    }

    $prdTeste = DB::connection('onda')->select(
        "SELECT IdDoc, CodCommessa, CodArt FROM PRDDocTeste WHERE CodCommessa = ?",
        [$t->CodCommessa]
    );
    foreach ($prdTeste as $p) {
        echo "\n  PRD IdDoc={$p->IdDoc} CodArt={$p->CodArt}\n";

        $fasi = DB::connection('onda')->select(
            "SELECT CodFase, CodMacchina, QtaDaLavorare, CodUnMis, TipoRiga
             FROM PRDDocFasi WHERE IdDoc = ?",
            [$p->IdDoc]
        );
        echo "    Fasi PRD: " . count($fasi) . "\n";
        foreach ($fasi as $f) {
            echo "      Fase {$f->CodFase} | Macch={$f->CodMacchina} | Qta={$f->QtaDaLavorare}\n";
        }
    }
    echo "\n" . str_repeat('-', 70) . "\n";
}

echo "\n=== MES: Ordini + Fasi per $cerca ===\n";
$ordini = App\Models\Ordine::where('commessa', 'like', "%$cerca%")->with('fasi')->get();
foreach ($ordini as $o) {
    echo "ORDINE MES id={$o->id} cod_art={$o->cod_art} descr=" . substr($o->descrizione ?? '', 0, 80) . "\n";
    foreach ($o->fasi as $f) {
        echo "  Fase {$f->fase} stato={$f->stato} qta_carta={$f->qta_carta} qta_fase={$f->qta_fase}\n";
    }
}
