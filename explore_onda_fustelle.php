<?php
/**
 * Esplorazione tabelle fustelle su Onda
 * Eseguire sul server: php explore_onda_fustelle.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$conn = DB::connection('onda');

echo "=== ESPLORAZIONE FUSTELLE ONDA ===\n\n";

// 1. Struttura OC_MAGFustellaRighe
echo "--- 1. Struttura OC_MAGFustellaRighe ---\n";
try {
    $cols = $conn->select("SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'OC_MAGFustellaRighe'
        ORDER BY ORDINAL_POSITION");
    foreach ($cols as $c) {
        $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
        echo "  {$c->COLUMN_NAME} [{$c->DATA_TYPE}{$len}]\n";
    }

    $count = $conn->selectOne("SELECT COUNT(*) as tot FROM OC_MAGFustellaRighe");
    echo "\n  Totale righe: {$count->tot}\n";

    echo "\n  Primi 10 record:\n";
    $rows = $conn->select("SELECT TOP 10 * FROM OC_MAGFustellaRighe");
    foreach ($rows as $row) {
        echo "  " . json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
    }
} catch (\Exception $e) {
    echo "  ERRORE: " . $e->getMessage() . "\n";
}

// 2. MAGAnagraficaArticoli — colonne fustella
echo "\n--- 2. MAGAnagraficaArticoli — colonne con 'fust' ---\n";
try {
    $cols = $conn->select("SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'MAGAnagraficaArticoli' AND COLUMN_NAME LIKE '%[Ff]ust%'
        ORDER BY ORDINAL_POSITION");
    foreach ($cols as $c) {
        $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
        echo "  {$c->COLUMN_NAME} [{$c->DATA_TYPE}{$len}]\n";
    }

    echo "\n  Primi 20 articoli con OC_CodFustella non vuoto:\n";
    $rows = $conn->select("SELECT TOP 20 CodArticolo, Descrizione, OC_CodFustella
        FROM MAGAnagraficaArticoli
        WHERE OC_CodFustella IS NOT NULL AND OC_CodFustella != ''
        ORDER BY CodArticolo");
    foreach ($rows as $row) {
        echo "  {$row->CodArticolo} | {$row->OC_CodFustella} | " . mb_substr($row->Descrizione ?? '', 0, 60) . "\n";
    }

    $countFust = $conn->selectOne("SELECT COUNT(*) as tot FROM MAGAnagraficaArticoli WHERE OC_CodFustella IS NOT NULL AND OC_CodFustella != ''");
    echo "\n  Articoli con fustella: {$countFust->tot}\n";
} catch (\Exception $e) {
    echo "  ERRORE: " . $e->getMessage() . "\n";
}

// 3. PRDDocFasi — OC_CodFustella
echo "\n--- 3. PRDDocFasi.OC_CodFustella ---\n";
try {
    $cols = $conn->select("SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'PRDDocFasi' AND COLUMN_NAME LIKE '%[Ff]ust%'
        ORDER BY ORDINAL_POSITION");
    foreach ($cols as $c) {
        $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
        echo "  {$c->COLUMN_NAME} [{$c->DATA_TYPE}{$len}]\n";
    }

    echo "\n  Primi 20 fasi con OC_CodFustella non vuoto:\n";
    $rows = $conn->select("SELECT TOP 20 t.CodCommessa, f.CodFase, f.OC_CodFustella
        FROM PRDDocFasi f
        JOIN PRDDocTeste t ON f.IdDoc = t.IdDoc
        WHERE f.OC_CodFustella IS NOT NULL AND f.OC_CodFustella != ''
        ORDER BY t.CodCommessa DESC");
    foreach ($rows as $row) {
        echo "  {$row->CodCommessa} | {$row->CodFase} | {$row->OC_CodFustella}\n";
    }

    $countFust = $conn->selectOne("SELECT COUNT(*) as tot FROM PRDDocFasi WHERE OC_CodFustella IS NOT NULL AND OC_CodFustella != ''");
    echo "\n  Fasi con fustella: {$countFust->tot}\n";
} catch (\Exception $e) {
    echo "  ERRORE: " . $e->getMessage() . "\n";
}

// 4. OC_ATTDocCartotecnica
echo "\n--- 4. Struttura OC_ATTDocCartotecnica ---\n";
try {
    $cols = $conn->select("SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'OC_ATTDocCartotecnica'
        ORDER BY ORDINAL_POSITION");
    foreach ($cols as $c) {
        $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
        echo "  {$c->COLUMN_NAME} [{$c->DATA_TYPE}{$len}]\n";
    }

    $count = $conn->selectOne("SELECT COUNT(*) as tot FROM OC_ATTDocCartotecnica");
    echo "\n  Totale righe: {$count->tot}\n";

    echo "\n  Primi 5 record:\n";
    $rows = $conn->select("SELECT TOP 5 * FROM OC_ATTDocCartotecnica");
    foreach ($rows as $row) {
        echo "  " . json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
    }
} catch (\Exception $e) {
    echo "  ERRORE: " . $e->getMessage() . "\n";
}

echo "\n=== FINE ESPLORAZIONE ===\n";
