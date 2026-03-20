<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = '0066816-26';

echo "=== DETTAGLIO COMPLETO ONDA: {$commessa} ===" . PHP_EOL . PHP_EOL;

// ATTDocTeste
$testa = DB::connection('onda')->select("SELECT * FROM ATTDocTeste WHERE CodCommessa = ?", [$commessa]);
echo "--- ATTDocTeste ---" . PHP_EOL;
foreach ($testa as $t) {
    echo "  IdDoc: {$t->IdDoc}" . PHP_EOL;
    echo "  CodCommessa: {$t->CodCommessa}" . PHP_EOL;
    echo "  TipoDocumento: {$t->TipoDocumento}" . PHP_EOL;
    echo "  DataRegistrazione: {$t->DataRegistrazione}" . PHP_EOL;
    echo "  IdAnagrafica: {$t->IdAnagrafica}" . PHP_EOL;
}

// ATTDocRighe
$righeAtt = DB::connection('onda')->select("
    SELECT r.IdRiga, r.NrRiga, r.CodArt, r.Descrizione, r.Qta, r.CodUnMis, r.DataPresConsegna
    FROM ATTDocRighe r
    JOIN ATTDocTeste t ON r.IdDoc = t.IdDoc
    WHERE t.CodCommessa = ?
    ORDER BY r.NrRiga
", [$commessa]);

echo PHP_EOL . "--- ATTDocRighe (righe commessa) ---" . PHP_EOL;
foreach ($righeAtt as $r) {
    echo "  Riga {$r->NrRiga} | Art:{$r->CodArt} | Desc:" . substr($r->Descrizione ?? '', 0, 80) . " | Qta:{$r->Qta} {$r->CodUnMis} | Consegna:{$r->DataPresConsegna}" . PHP_EOL;
}

// PRDDocTeste (ordini produzione)
$prdTeste = DB::connection('onda')->select("SELECT * FROM PRDDocTeste WHERE CodCommessa = ?", [$commessa]);
echo PHP_EOL . "--- PRDDocTeste (ordini produzione) ---" . PHP_EOL;
foreach ($prdTeste as $p) {
    echo "  IdDoc:{$p->IdDoc} | CodArt:{$p->CodArt} | Desc:" . substr($p->OC_Descrizione ?? '', 0, 80) . PHP_EOL;
    echo "  QtaDaProdurre:{$p->QtaDaProdurre} | DataPresConsegna:{$p->DataPresConsegna}" . PHP_EOL;

    // PRDDocFasi
    $fasi = DB::connection('onda')->select("SELECT * FROM PRDDocFasi WHERE IdDoc = ? ORDER BY Sequenza", [$p->IdDoc]);
    echo "  FASI ({$p->IdDoc}):" . PHP_EOL;
    foreach ($fasi as $f) {
        echo "    Seq:{$f->Sequenza} | CodFase:{$f->CodFase} | Macchina:{$f->CodMacchina} | Qta:{$f->QtaDaLavorare} {$f->CodUnMis}";
        // Mostra tutti i campi non-null
        $extra = [];
        if (!empty($f->OC_CodFustella)) $extra[] = "Fustella:{$f->OC_CodFustella}";
        if (!empty($f->DescrizioneFase)) $extra[] = "DescFase:{$f->DescrizioneFase}";
        if (!empty($f->Esternalizzata)) $extra[] = "Esternalizzata:{$f->Esternalizzata}";
        if (!empty($extra)) echo " | " . implode(' | ', $extra);
        echo PHP_EOL;
    }

    // PRDDocRighe (materiali)
    $materiali = DB::connection('onda')->select("
        SELECT r.CodArt, r.Descrizione, r.Qta, r.CodUnMis, r.Sequenza
        FROM PRDDocRighe r WHERE r.IdDoc = ? ORDER BY r.Sequenza
    ", [$p->IdDoc]);
    echo "  MATERIALI ({$p->IdDoc}):" . PHP_EOL;
    foreach ($materiali as $m) {
        echo "    Seq:{$m->Sequenza} | Art:{$m->CodArt} | Desc:" . substr($m->Descrizione ?? '', 0, 60) . " | Qta:{$m->Qta} {$m->CodUnMis}" . PHP_EOL;
    }
    echo PHP_EOL;
}

// Verifica se 'Allest.Manuale' (senza EXT) esiste come fase in Onda
echo "--- RICERCA SPECIFICA ---" . PHP_EOL;
$cerca = DB::connection('onda')->select("
    SELECT f.CodFase, f.CodMacchina, f.QtaDaLavorare, p.CodCommessa
    FROM PRDDocFasi f
    JOIN PRDDocTeste p ON f.IdDoc = p.IdDoc
    WHERE p.CodCommessa = ?
    AND (f.CodFase LIKE '%Allest%' OR f.CodFase LIKE '%allest%' OR f.CodFase LIKE '%EXT%')
", [$commessa]);

echo "  Fasi con 'Allest' o 'EXT' nel nome:" . PHP_EOL;
foreach ($cerca as $c) {
    echo "    CodFase: [{$c->CodFase}] | Macchina:{$c->CodMacchina} | Qta:{$c->QtaDaLavorare}" . PHP_EOL;
}
if (empty($cerca)) echo "    (nessuna trovata)" . PHP_EOL;
