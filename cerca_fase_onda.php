<?php
// Uso: php cerca_fase_onda.php 66881
// Cerca una commessa in TUTTE le tabelle Onda
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cerca = $argv[1] ?? '66881';

echo "=== CERCA '{$cerca}' OVUNQUE SU ONDA ===\n\n";

// 1. Documenti
echo "--- ATTDocTeste ---\n";
$docs = DB::connection('onda')->select("
    SELECT t.IdDoc, t.TipoDocumento, t.NumeroDocumento, t.CodCommessa, t.DataRegistrazione,
           a.RagioneSociale
    FROM ATTDocTeste t
    LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
    WHERE t.CodCommessa LIKE ? OR t.NumeroDocumento LIKE ?
    ORDER BY t.DataRegistrazione
", ["%{$cerca}%", "%{$cerca}%"]);
foreach ($docs as $d) {
    echo "  IdDoc:{$d->IdDoc} | Tipo:{$d->TipoDocumento} | Num:{$d->NumeroDocumento} | Comm:{$d->CodCommessa} | {$d->RagioneSociale}\n";
}
if (empty($docs)) echo "  Nessuno\n";

// 2. Righe documenti
echo "\n--- ATTDocRighe (desc con '{$cerca}') ---\n";
$righe = DB::connection('onda')->select("
    SELECT TOP 10 t.IdDoc, t.TipoDocumento, t.NumeroDocumento, t.CodCommessa,
           a.RagioneSociale, r.CodArt, r.Descrizione
    FROM ATTDocTeste t
    JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
    LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
    WHERE r.Descrizione LIKE ? OR r.CodArt LIKE ?
", ["%{$cerca}%", "%{$cerca}%"]);
foreach ($righe as $r) {
    echo "  Tipo:{$r->TipoDocumento} | Num:{$r->NumeroDocumento} | {$r->RagioneSociale} | Desc:" . substr($r->Descrizione, 0, 120) . "\n";
}
if (empty($righe)) echo "  Nessuno\n";

// 3. Produzione - fasi
echo "\n--- PRDDocFasi (TUTTE le fasi) ---\n";
$prdIds = DB::connection('onda')->select("
    SELECT p.IdDoc FROM PRDDocTeste p WHERE p.CodCommessa LIKE ?
", ["%{$cerca}%"]);
$ids = array_map(fn($p) => $p->IdDoc, $prdIds);
if (!empty($ids)) {
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $fasi = DB::connection('onda')->select("
        SELECT f.CodFase, f.Descrizione, f.QtaDaLavorare, f.CodUnMis, f.TipoRiga, f.Sequenza
        FROM PRDDocFasi f WHERE f.IdDoc IN ({$ph}) ORDER BY f.Sequenza
    ", $ids);
    foreach ($fasi as $f) {
        $tag = (stripos($f->CodFase, 'tagli') !== false || stripos($f->Descrizione, 'tagli') !== false) ? ' *** TAGLIO ***' : '';
        echo "  Seq:{$f->Sequenza} | {$f->CodFase} | {$f->Descrizione} | Qta:{$f->QtaDaLavorare} {$f->CodUnMis}{$tag}\n";
    }
} else {
    echo "  Nessun documento produzione trovato\n";
}

// 4. Produzione - righe materiali
echo "\n--- PRDDocRighe (materiali) ---\n";
if (!empty($ids)) {
    $mat = DB::connection('onda')->select("
        SELECT r.CodArt, r.Descrizione, r.Qta, r.CodUnMis
        FROM PRDDocRighe r WHERE r.IdDoc IN ({$ph}) ORDER BY r.Sequenza
    ", $ids);
    foreach ($mat as $m) {
        $tag = (stripos($m->Descrizione, 'tagli') !== false) ? ' *** TAGLIO ***' : '';
        echo "  Art:{$m->CodArt} | {$m->Descrizione} | Qta:{$m->Qta} {$m->CodUnMis}{$tag}\n";
    }
}

// 5. MES
echo "\n--- MES ---\n";
$commessaCode = strlen($cerca) <= 7 ? str_pad($cerca, 7, '0', STR_PAD_LEFT) . '-26' : $cerca;
$fasiMes = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'f.ordine_id', '=', 'o.id')
    ->where('o.commessa', $commessaCode)
    ->select('f.fase', 'f.stato', 'f.esterno', 'f.note')
    ->get();
foreach ($fasiMes as $f) {
    $ext = $f->esterno ? ' [EXT]' : '';
    $tag = (stripos($f->fase, 'tagli') !== false) ? ' *** TAGLIO ***' : '';
    echo "  {$f->fase} | stato:{$f->stato}{$ext}{$tag}\n";
}
if ($fasiMes->isEmpty()) echo "  Nessuna fase nel MES\n";

echo "\nDONE\n";
