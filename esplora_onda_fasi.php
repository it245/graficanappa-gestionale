<?php
/**
 * Interroga Onda per vedere tutti i campi delle fasi di una commessa
 * Eseguire sul server .60: php esplora_onda_fasi.php 0066622
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = $argv[1] ?? '0066622-26';
echo "=== COMMESSA: $commessa ===" . PHP_EOL . PHP_EOL;

// 1. Dati testata ordine
$testata = DB::connection('onda')->select("
    SELECT
        t.CodCommessa,
        p.CodArt,
        p.OC_Descrizione,
        p.QtaDaProdurre,
        p.DataPresConsegna,
        carta.CodArt AS CodCarta,
        carta.Descrizione AS DescrizioneCarta,
        carta.Qta AS QtaCarta,
        carta.CodUnMis AS UMCarta
    FROM ATTDocTeste t
    INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
    OUTER APPLY (
        SELECT TOP 1 r.CodArt, r.Descrizione, r.Qta, r.CodUnMis
        FROM PRDDocRighe r WHERE r.IdDoc = p.IdDoc
        ORDER BY r.Sequenza
    ) carta
    WHERE t.CodCommessa LIKE ?
", ["%$commessa%"]);

if (empty($testata)) {
    echo "Commessa non trovata!" . PHP_EOL;
    exit(1);
}

$t = $testata[0];
echo "CodArt: {$t->CodArt}" . PHP_EOL;
echo "Descrizione: {$t->OC_Descrizione}" . PHP_EOL;
echo "QtaDaProdurre: {$t->QtaDaProdurre}" . PHP_EOL;
echo "Carta: {$t->DescrizioneCarta}" . PHP_EOL;
echo "QtaCarta: {$t->QtaCarta} {$t->UMCarta}" . PHP_EOL;
echo PHP_EOL;

// 2. Tutte le righe materiali
echo "=== RIGHE MATERIALI (PRDDocRighe) ===" . PHP_EOL;
$righe = DB::connection('onda')->select("
    SELECT r.*
    FROM PRDDocRighe r
    INNER JOIN PRDDocTeste p ON r.IdDoc = p.IdDoc
    INNER JOIN ATTDocTeste t ON t.CodCommessa = p.CodCommessa
    WHERE t.CodCommessa LIKE ?
    ORDER BY r.Sequenza
", ["%$commessa%"]);

foreach ($righe as $r) {
    $vals = (array)$r;
    foreach ($vals as $k => $v) {
        if ($v !== null && $v !== '' && $v !== 0 && $v !== '0' && $v !== 0.0) {
            echo "  $k: $v" . PHP_EOL;
        }
    }
    echo "  ---" . PHP_EOL;
}

// 3. Tutte le fasi con TUTTI i campi
echo PHP_EOL . "=== FASI (PRDDocFasi) ===" . PHP_EOL;
$fasi = DB::connection('onda')->select("
    SELECT f.*
    FROM PRDDocFasi f
    INNER JOIN PRDDocTeste p ON f.IdDoc = p.IdDoc
    INNER JOIN ATTDocTeste t ON t.CodCommessa = p.CodCommessa
    WHERE t.CodCommessa LIKE ?
    ORDER BY f.Sequenza
", ["%$commessa%"]);

foreach ($fasi as $f) {
    echo PHP_EOL . "--- FASE: {$f->CodFase} (Macchina: " . ($f->CodMacchina ?? '-') . ") ---" . PHP_EOL;
    $vals = (array)$f;
    foreach ($vals as $k => $v) {
        if ($v !== null && $v !== '' && $v !== 0 && $v !== '0' && $v !== 0.0) {
            echo "  $k: $v" . PHP_EOL;
        }
    }
}

// 4. Colonne disponibili in PRDDocFasi
echo PHP_EOL . "=== COLONNE PRDDocFasi ===" . PHP_EOL;
$cols = DB::connection('onda')->select("
    SELECT COLUMN_NAME, DATA_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'PRDDocFasi'
    ORDER BY ORDINAL_POSITION
");
foreach ($cols as $c) {
    echo "  {$c->COLUMN_NAME} ({$c->DATA_TYPE})" . PHP_EOL;
}

echo PHP_EOL . "=== FINE ===" . PHP_EOL;
