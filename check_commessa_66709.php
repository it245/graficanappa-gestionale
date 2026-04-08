<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

error_reporting(E_ALL & ~E_DEPRECATED);

echo "=== MES: ORDINI COMMESSA 0066709-26 ===\n";
$ordini = App\Models\Ordine::where('commessa', '0066709-26')->with('fasi')->get();
foreach ($ordini as $o) {
    echo "  ID={$o->id} desc=[{$o->descrizione}] qta={$o->qta_richiesta} cod_art={$o->cod_art}\n";
    foreach ($o->fasi as $f) {
        echo "    Fase: {$f->fase} stato={$f->stato} qta_carta={$f->qta_carta} qta_fase={$f->qta_fase}\n";
    }
}
echo "Totale ordini: " . $ordini->count() . " | Totale fasi: " . $ordini->sum(fn($o) => $o->fasi->count()) . "\n";

echo "\n=== ONDA: FASI PRODUZIONE 66709 ===\n";
$righe = DB::connection('onda')->select("
    SELECT p.IdDoc, p.CodArt, p.OC_Descrizione, p.QtaDaProdurre,
           f.CodFase, f.QtaDaLavorare, f.CodUnMis, f.CodMacchina
    FROM PRDDocTeste p
    LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
    WHERE p.CodCommessa LIKE '%66709%'
    ORDER BY p.IdDoc, f.CodFase
");
$currentDoc = null;
foreach ($righe as $r) {
    if ($r->IdDoc !== $currentDoc) {
        $currentDoc = $r->IdDoc;
        echo "\n  IdDoc={$r->IdDoc} Art=[{$r->CodArt}] Desc=[{$r->OC_Descrizione}] Qta={$r->QtaDaProdurre}\n";
    }
    if ($r->CodFase) {
        echo "    Fase: {$r->CodFase} Qta={$r->QtaDaLavorare} UM={$r->CodUnMis} Mac={$r->CodMacchina}\n";
    }
}

// Cerca in TUTTI i documenti Onda (anche ATTDocTeste, non solo PRD)
echo "\n=== ONDA: TUTTI I DOCUMENTI COMMESSA 66709 ===\n";
$docs = DB::connection('onda')->select("
    SELECT t.IdDoc, t.TipoDocumento, t.NumeroDocumento, t.DataDocumento, t.CodCommessa,
           a.RagioneSociale AS Cliente
    FROM ATTDocTeste t
    LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
    WHERE t.CodCommessa LIKE '%66709%'
    ORDER BY t.TipoDocumento, t.DataDocumento
");
foreach ($docs as $d) {
    $tipo = match((int)$d->TipoDocumento) {
        1 => 'Preventivo', 2 => 'Ordine', 3 => 'DDT Vendita', 4 => 'Fattura',
        5 => 'Ordine Fornitore', 6 => 'Carico Fornitore', 7 => 'DDT Fornitore',
        default => "Tipo {$d->TipoDocumento}",
    };
    echo "  Tipo={$tipo} Num={$d->NumeroDocumento} Data={$d->DataDocumento} Comm={$d->CodCommessa}\n";
}

// Cerca righe ATTDocRighe per vedere se c'è PI01 nelle righe dell'ordine
echo "\n=== ONDA: RIGHE ORDINE ATTIVO 66709 (cerca PI) ===\n";
$righeAtt = DB::connection('onda')->select("
    SELECT t.IdDoc, t.TipoDocumento, r.CodArt, r.Descrizione, r.Qta, r.CodUnMis
    FROM ATTDocTeste t
    JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
    WHERE t.CodCommessa LIKE '%66709%'
      AND (r.CodArt LIKE '%PI%' OR r.Descrizione LIKE '%piega%' OR r.Descrizione LIKE '%incolla%')
    ORDER BY t.IdDoc
");
if (empty($righeAtt)) {
    echo "  Nessuna riga con PI/piega/incolla trovata\n";
} else {
    foreach ($righeAtt as $r) {
        echo "  IdDoc={$r->IdDoc} Tipo={$r->TipoDocumento} Art=[{$r->CodArt}] Desc=[{$r->Descrizione}] Qta={$r->Qta}\n";
    }
}

// Cerca nella descrizione dell'ordine se menziona piegaincolla
echo "\n=== ONDA: DESCRIZIONE ORDINE (cerca piega/incolla) ===\n";
$descOrdine = DB::connection('onda')->select("
    SELECT t.OC_CommentoProduz, t.ncpcommentoprestampa
    FROM ATTDocTeste t
    WHERE t.CodCommessa LIKE '%66709%'
      AND t.TipoDocumento = 2
");
foreach ($descOrdine as $d) {
    echo "  Commento produzione: " . ($d->OC_CommentoProduz ?: '(vuoto)') . "\n";
    echo "  Commento prestampa: " . ($d->ncpcommentoprestampa ?: '(vuoto)') . "\n";
}
