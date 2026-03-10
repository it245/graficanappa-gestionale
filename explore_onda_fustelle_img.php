<?php
/**
 * Cerca immagini/tracciati fustelle su Onda
 * Eseguire sul server: php explore_onda_fustelle_img.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$onda = DB::connection('onda');

echo "=== RICERCA IMMAGINI/TRACCIATI FUSTELLE ===\n\n";

// 1. Cerco colonne con "img", "image", "file", "path", "tracciato", "disegno", "pdf" in OC_MAGFustellaRighe
echo "--- 1. Colonne OC_MAGFustellaRighe (tutte) ---\n";
$cols = $onda->select("SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
    FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'OC_MAGFustellaRighe' ORDER BY ORDINAL_POSITION");
foreach ($cols as $c) {
    $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
    echo "  {$c->COLUMN_NAME} [{$c->DATA_TYPE}{$len}]\n";
}

// 2. Cerco tabelle con "fustella" nel nome
echo "\n--- 2. Tabelle con 'fustella' nel nome ---\n";
$tables = $onda->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '%[Ff]ustella%' OR TABLE_NAME LIKE '%FUST%'");
foreach ($tables as $t) {
    $count = $onda->selectOne("SELECT COUNT(*) as tot FROM [{$t->TABLE_NAME}]");
    echo "  {$t->TABLE_NAME} ({$count->tot} righe)\n";
}

// 3. Cerco tabelle con "immagini", "allegati", "file", "documenti"
echo "\n--- 3. Tabelle per immagini/allegati ---\n";
$patterns = ['%[Ii]mmagin%', '%[Aa]llegat%', '%[Ff]ile%', '%[Dd]ocument%', '%[Tt]raccia%', '%[Dd]isegn%'];
foreach ($patterns as $p) {
    $tables = $onda->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE ?", [$p]);
    foreach ($tables as $t) {
        $count = $onda->selectOne("SELECT COUNT(*) as tot FROM [{$t->TABLE_NAME}]");
        echo "  {$t->TABLE_NAME} ({$count->tot} righe)\n";
    }
}

// 4. Colonne con "img", "file", "path", "blob", "image" in MAGAnagraficaArticoli
echo "\n--- 4. MAGAnagraficaArticoli — colonne file/immagine ---\n";
$cols = $onda->select("SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'MAGAnagraficaArticoli'
    AND (COLUMN_NAME LIKE '%[Ii]mg%' OR COLUMN_NAME LIKE '%[Ff]ile%' OR COLUMN_NAME LIKE '%[Pp]ath%'
         OR COLUMN_NAME LIKE '%[Ii]mage%' OR COLUMN_NAME LIKE '%[Bb]lob%' OR COLUMN_NAME LIKE '%[Ff]oto%'
         OR COLUMN_NAME LIKE '%[Dd]isegn%' OR COLUMN_NAME LIKE '%[Tt]raccia%')
    ORDER BY ORDINAL_POSITION");
if (count($cols) > 0) {
    foreach ($cols as $c) {
        $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
        echo "  {$c->COLUMN_NAME} [{$c->DATA_TYPE}{$len}]\n";
    }
} else {
    echo "  Nessuna colonna immagine trovata\n";
}

// 5. Cerco nella tabella OC_MAGFustellaRighe se Note contiene path
echo "\n--- 5. OC_MAGFustellaRighe — Note non vuote (primi 10) ---\n";
$rows = $onda->select("SELECT TOP 10 CodArt, CodNeutro, Note FROM OC_MAGFustellaRighe WHERE Note IS NOT NULL AND Note != ''");
if (count($rows) > 0) {
    foreach ($rows as $r) {
        echo "  {$r->CodNeutro}: " . mb_substr($r->Note, 0, 100) . "\n";
    }
} else {
    echo "  Nessuna nota presente\n";
}

// 6. Struttura MAGAnagraficaArticoli — colonna PK e tutte le colonne OC_
echo "\n--- 6. MAGAnagraficaArticoli — PK e colonne OC_ ---\n";
$cols = $onda->select("SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'MAGAnagraficaArticoli'
    AND (COLUMN_NAME LIKE 'Id%' OR COLUMN_NAME LIKE 'Cod%' OR COLUMN_NAME LIKE 'OC_%')
    ORDER BY ORDINAL_POSITION");
foreach ($cols as $c) {
    $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
    echo "  {$c->COLUMN_NAME} [{$c->DATA_TYPE}{$len}]\n";
}

// 7. Esempio articoli fustella da MAGAnagraficaArticoli
echo "\n--- 7. Articoli fustella (primi 15 con OC_CodFustella) ---\n";
try {
    // Trovo il nome colonna PK
    $pk = $onda->selectOne("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = 'MAGAnagraficaArticoli' AND CONSTRAINT_NAME LIKE 'PK%'");
    $pkCol = $pk ? $pk->COLUMN_NAME : 'CodArt';
    echo "  PK: $pkCol\n";

    $rows = $onda->select("SELECT TOP 15 [$pkCol], Descrizione, OC_CodFustella
        FROM MAGAnagraficaArticoli
        WHERE OC_CodFustella IS NOT NULL AND OC_CodFustella != ''
        ORDER BY [$pkCol] DESC");
    foreach ($rows as $r) {
        $desc = mb_substr($r->Descrizione ?? '', 0, 50);
        echo "  {$r->$pkCol} | {$r->OC_CodFustella} | $desc\n";
    }
} catch (\Exception $e) {
    echo "  ERRORE: " . $e->getMessage() . "\n";
}

echo "\n=== FINE ===\n";
