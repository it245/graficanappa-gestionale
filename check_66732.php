<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== ONDA: Commessa 66732 ===\n";
// Colonne disponibili
$colsT = DB::connection('onda')->select(
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ATTDocTeste' ORDER BY ORDINAL_POSITION"
);
echo "Colonne ATTDocTeste: ";
echo implode(', ', array_map(fn($c) => $c->COLUMN_NAME, $colsT)) . "\n\n";

$righe = DB::connection('onda')->select(
    "SELECT r.CodCommessa, r.CodArt, r.Descrizione, r.Qta, r.CodUnMis, r.DataConsegna, r.DataPresConsegna,
            r.OC_Tiratura, r.OC_Pagine, r.OC_Base, r.OC_Altezza, r.Priorita,
            t.IdAnagrafica, t.DataRegistrazione, t.OC_CommentoProduz, t.ncpcommentoprestampa
     FROM ATTDocRighe r
     JOIN ATTDocTeste t ON r.IdDoc = t.IdDoc
     WHERE r.CodCommessa LIKE '%66732%'
     ORDER BY r.CodCommessa, r.NrRiga"
);

if (empty($righe)) {
    echo "Nessun risultato in Onda.\n";
} else {
    foreach ($righe as $r) {
        echo "Commessa: {$r->CodCommessa}\n";
        echo "  IdAnagrafica: {$r->IdAnagrafica}\n";
        echo "  CodArt: {$r->CodArt}\n";
        echo "  Descrizione: {$r->Descrizione}\n";
        echo "  Qta: {$r->Qta} {$r->CodUnMis}\n";
        echo "  Tiratura: {$r->OC_Tiratura} | Pagine: {$r->OC_Pagine} | Base: {$r->OC_Base} | Alt: {$r->OC_Altezza}\n";
        echo "  Data Consegna: {$r->DataConsegna}\n";
        echo "  Data Pres Consegna: {$r->DataPresConsegna}\n";
        echo "  Data Reg: {$r->DataRegistrazione}\n";
        echo "  Priorita: {$r->Priorita}\n";
        echo "  Commento Prod: {$r->OC_CommentoProduz}\n";
        echo "  Note Prestampa: {$r->ncpcommentoprestampa}\n";
        echo "---\n";
    }
}

echo "\n=== MES: Commessa 0066732-26 ===\n";
$ordini = App\Models\Ordine::where('commessa', 'like', '%66732%')->get();
foreach ($ordini as $o) {
    echo "Commessa: {$o->commessa}\n";
    echo "  Cliente: {$o->cliente_nome}\n";
    echo "  Descrizione: {$o->descrizione}\n";
    echo "  Cod Art: {$o->cod_art}\n";
    echo "  Qta Richiesta: {$o->qta_richiesta}\n";
    echo "  Qta Carta: {$o->qta_carta}\n";
    echo "  UM Carta: {$o->UM_carta}\n";
    echo "  Carta: {$o->carta}\n";
    echo "  Cod Carta: {$o->cod_carta}\n";
    echo "  Data Consegna: {$o->data_prevista_consegna}\n";
    echo "  Note Pre: {$o->note_prestampa}\n";
    echo "  Commento: {$o->commento_produzione}\n";

    $fasi = App\Models\OrdineFase::where('ordine_id', $o->id)
        ->with('faseCatalogo.reparto')
        ->orderBy('priorita')
        ->get();
    echo "  FASI:\n";
    foreach ($fasi as $f) {
        $rep = optional(optional($f->faseCatalogo)->reparto)->nome ?? '-';
        echo "    {$f->fase} | Rep: {$rep} | Stato: {$f->stato} | QtaProd: {$f->qta_prod} | Prio: {$f->priorita} | Esterno: " . ($f->esterno ? 'SI' : 'NO') . "\n";
    }
    echo "---\n";
}
