<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = '0066667-26';

echo "=== ONDA DETTAGLIO: {$commessa} ===" . PHP_EOL;

$prd = DB::connection('onda')->select("
    SELECT p.IdDoc, p.CodArt, p.OC_Descrizione, p.QtaDaProdurre
    FROM PRDDocTeste p WHERE p.CodCommessa = ?
", [$commessa]);

foreach ($prd as $p) {
    echo PHP_EOL . "Ordine PRD {$p->IdDoc} | Art:{$p->CodArt} | Desc:" . substr($p->OC_Descrizione ?? '', 0, 50) . PHP_EOL;

    $fasi = DB::connection('onda')->select("
        SELECT f.Sequenza, f.CodFase, f.TipoRiga, f.CodMacchina, f.QtaDaLavorare, f.CodUnMis
        FROM PRDDocFasi f WHERE f.IdDoc = ? ORDER BY f.Sequenza
    ", [$p->IdDoc]);

    foreach ($fasi as $f) {
        $ext = str_starts_with(strtoupper($f->CodFase), 'EXT') ? ' ← EXT!' : '';
        $tipo = $f->TipoRiga == 2 ? ' [TIPO=2 ESTERNA]' : ' [TIPO=1 INTERNA]';
        echo "  Seq:{$f->Sequenza} | {$f->CodFase} | qta:{$f->QtaDaLavorare} {$f->CodUnMis}{$tipo}{$ext}" . PHP_EOL;
    }
}

echo PHP_EOL . "ATTDocRighe (solo fasi):" . PHP_EOL;
$righe = DB::connection('onda')->select("
    SELECT r.NrRiga, r.CodArt, r.Qta, r.CodUnMis
    FROM ATTDocRighe r JOIN ATTDocTeste t ON r.IdDoc = t.IdDoc
    WHERE t.CodCommessa = ? ORDER BY r.NrRiga
", [$commessa]);
foreach ($righe as $r) {
    if (preg_match('/^(EXT|BRT|STAMPA|PLA|FUST|PI0|FIN|TAGLIO|accopp|ALLEST|ARROT|UVSPOT)/i', $r->CodArt)) {
        echo "  Riga {$r->NrRiga} | {$r->CodArt} | qta:{$r->Qta} {$r->CodUnMis}" . PHP_EOL;
    }
}

// DDT fornitore per questa commessa
echo PHP_EOL . "DDT Fornitore (TipoDocumento=11):" . PHP_EOL;
$ddtForn = DB::connection('onda')->select("
    SELECT t.IdDoc, t.CodCommessa, t.DataRegistrazione,
           a.RagioneSociale as Fornitore,
           r.CodArt, r.Descrizione, r.Qta
    FROM ATTDocTeste t
    JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
    LEFT JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
    WHERE t.CodCommessa = ?
      AND t.TipoDocumento IN ('11', '4', '5')
    ORDER BY t.DataRegistrazione
", [$commessa]);

if (empty($ddtForn)) {
    echo "  Nessun DDT fornitore trovato" . PHP_EOL;
} else {
    foreach ($ddtForn as $d) {
        echo "  DDT {$d->IdDoc} | {$d->Fornitore} | Data:{$d->DataRegistrazione} | Art:{$d->CodArt} | Desc:" . substr($d->Descrizione ?? '', 0, 50) . " | qta:{$d->Qta}" . PHP_EOL;
    }
}
