<?php
// Confronto fasi Onda vs MES per commessa 0067502-26 (REPORT CROCE ROSSA)
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$comm = $argv[1] ?? '67502';
echo "=== COMMESSA $comm ===\n\n";

echo "--- ONDA: documenti commessa ---\n";
// Prima vedo che colonne esistono
$cols = DB::connection('onda')->select("SELECT TOP 1 * FROM PRDDocTeste");
echo "Colonne PRDDocTeste: " . implode(', ', array_keys((array)($cols[0] ?? []))) . "\n\n";

$docs = DB::connection('onda')->select(
    "SELECT t.*
     FROM PRDDocTeste t
     WHERE t.CodCommessa LIKE ?
     ORDER BY t.IdDoc",
    ["%$comm%"]
);
foreach ($docs as $d) {
    echo "  IdDoc={$d->IdDoc}  Comm={$d->CodCommessa}  Nome={$d->NomeOrdine}  Tipo={$d->TipoDocumento}  Cons={$d->DataConsegna}\n";
}

echo "\n--- ONDA: fasi (PRDDocFasi) ---\n";
foreach ($docs as $d) {
    echo "\n>>> IdDoc={$d->IdDoc} ({$d->NomeOrdine})\n";
    $fasi = DB::connection('onda')->select(
        "SELECT f.IdFase, f.IdDoc, f.CodFase, f.DescFase, f.TipoFase, f.Qta, f.IdReparto, f.OreLav, f.OreMacchina
         FROM PRDDocFasi f WHERE f.IdDoc = ? ORDER BY f.IdFase",
        [$d->IdDoc]
    );
    if (empty($fasi)) { echo "  (nessuna fase)\n"; continue; }
    foreach ($fasi as $f) {
        echo sprintf("  Fase=%-25s Tipo=%-15s Qta=%-8s Rep=%-3s OreLav=%-6s OreMac=%s\n",
            $f->CodFase, $f->TipoFase ?? '-', $f->Qta, $f->IdReparto, $f->OreLav ?? '-', $f->OreMacchina ?? '-');
    }
}

echo "\n--- ONDA: righe documento ---\n";
foreach ($docs as $d) {
    $righe = DB::connection('onda')->select(
        "SELECT r.IdDoc, r.CodArticolo, r.DesArticolo, r.Qta, r.UM, r.IdReparto
         FROM PRDDocRighe r WHERE r.IdDoc = ? ORDER BY r.IdDoc",
        [$d->IdDoc]
    );
    foreach ($righe as $r) {
        echo sprintf("  IdDoc=%s  CodArt=%-20s Desc=%-40s Qta=%-8s UM=%s  Rep=%s\n",
            $r->IdDoc, $r->CodArticolo, mb_substr($r->DesArticolo, 0, 40), $r->Qta, $r->UM ?? '-', $r->IdReparto);
    }
}

echo "\n--- MES: fasi ordine_fasi ---\n";
$ordini = App\Models\Ordine::with(['fasi.faseCatalogo.reparto'])->where('commessa', 'like', "%$comm%")->get();
foreach ($ordini as $ord) {
    echo "\n>>> Ordine MES id={$ord->id}  comm={$ord->commessa}  desc=" . mb_substr($ord->descrizione, 0, 50) . "\n";
    foreach ($ord->fasi as $f) {
        $rep = $f->faseCatalogo->reparto->nome ?? '?';
        echo sprintf("  Fase=%-25s Reparto=%-15s Qta=%-8s Stato=%-6s Fogli_buoni=%-8s Note=%s\n",
            $f->faseCatalogo->fase ?? '?', $rep, $f->qta_carta ?? '-', $f->stato, $f->fogli_buoni ?? '-', mb_substr($f->note ?? '', 0, 40));
    }
}

echo "\n=== FINE ===\n";
