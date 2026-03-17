<?php
// Uso: php check_commessa.php [commessa]
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ordine;
use App\Models\OrdineFase;

$cerca = $argv[1] ?? '66475';
$commessa = '00' . $cerca . '-26';
echo "=== COMMESSA $commessa ===\n\n";

// --- MES ---
echo "--- MES (MySQL) ---\n";
$ordini = Ordine::where('commessa', $commessa)->get();
echo "Ordini nel MES: " . $ordini->count() . "\n";
foreach ($ordini as $o) {
    echo "  ID: {$o->id} | Desc: " . substr($o->descrizione, 0, 60) . " | Qta: {$o->qta_richiesta}\n";
}

echo "\nFasi nel MES:\n";
$fasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
    ->with(['faseCatalogo.reparto', 'ordine'])
    ->orderBy('priorita')
    ->get();
echo "Totale fasi: " . $fasi->count() . "\n";
foreach ($fasi as $f) {
    $stati = ['Non iniziata', 'Pronto', 'Avviato', 'Terminato', 'Consegnato'];
    $reparto = $f->faseCatalogo->reparto->nome ?? '-';
    $statoLabel = isset($stati[$f->stato]) ? $stati[$f->stato] : '?';
    echo "  {$f->fase} | {$reparto} | Stato: {$f->stato} ({$statoLabel}) | OrdID: {$f->ordine_id}\n";
}

// --- ONDA ---
echo "\n--- ONDA (SQL Server) ---\n";

// Teste (commessa)
$teste = DB::connection('onda')->select(
    "SELECT t.IDDoc, t.CodCommessa, t.DataRegistrazione, t.TipoDocumento, t.CodCliFor, t.RagioneSociale
     FROM ATTDocTeste t
     WHERE t.CodCommessa LIKE ?
     ORDER BY t.IDDoc",
    ["%$cerca%"]
);
echo "Documenti Onda: " . count($teste) . "\n";
foreach ($teste as $t) {
    echo "  IDDoc: {$t->IDDoc} | {$t->CodCommessa} | {$t->RagioneSociale} | Tipo: {$t->TipoDocumento} | Data: {$t->DataRegistrazione}\n";
}

// Righe (ordini/articoli della commessa)
if (!empty($teste)) {
    $idDocs = array_map(fn($t) => $t->IDDoc, $teste);
    $placeholders = implode(',', array_fill(0, count($idDocs), '?'));

    $righe = DB::connection('onda')->select(
        "SELECT r.IDDoc, r.NumRiga, r.CodArticolo, r.Descrizione, r.Qta, r.UM, r.PrezzoUnitario
         FROM ATTDocRighe r
         WHERE r.IDDoc IN ($placeholders)
         ORDER BY r.IDDoc, r.NumRiga",
        $idDocs
    );

    echo "\nRighe Onda: " . count($righe) . "\n";
    foreach ($righe as $r) {
        $desc = substr($r->Descrizione ?? '-', 0, 60);
        echo "  IDDoc:{$r->IDDoc} Riga:{$r->NumRiga} | {$r->CodArticolo} | {$desc} | Qta:{$r->Qta} {$r->UM}\n";
    }
}
