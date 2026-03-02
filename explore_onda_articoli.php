<?php
/**
 * Script debug: esplora il database Onda per trovare tabelle articoli/anagrafiche
 * e colonne relative a fustella.
 *
 * Eseguire: php explore_onda_articoli.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$conn = DB::connection('onda');

echo "=== ESPLORAZIONE DATABASE ONDA - ARTICOLI E FUSTELLA ===\n\n";

// 1. Trova tabelle che contengono "art", "anag", "prod" nel nome
echo "--- 1. TABELLE CON NOMI RILEVANTI (art, anag, prod, magaz) ---\n";
$tabelle = $conn->select("
    SELECT TABLE_NAME, TABLE_TYPE
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_TYPE = 'BASE TABLE'
      AND (TABLE_NAME LIKE '%Art%' OR TABLE_NAME LIKE '%Anag%'
           OR TABLE_NAME LIKE '%Prod%' OR TABLE_NAME LIKE '%Magaz%'
           OR TABLE_NAME LIKE '%Fust%')
    ORDER BY TABLE_NAME
");
foreach ($tabelle as $t) {
    echo "  {$t->TABLE_NAME} ({$t->TABLE_TYPE})\n";
}
echo "\n";

// 2. Cerca colonne con "fust", "FS", "fustella" in tutte le tabelle
echo "--- 2. COLONNE CON NOME CONTENENTE 'fust' o 'fustella' ---\n";
$colonneFustella = $conn->select("
    SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE (COLUMN_NAME LIKE '%fust%' OR COLUMN_NAME LIKE '%Fust%' OR COLUMN_NAME LIKE '%FUST%')
    ORDER BY TABLE_NAME, COLUMN_NAME
");
if (empty($colonneFustella)) {
    echo "  Nessuna colonna trovata con 'fust' nel nome.\n";
} else {
    foreach ($colonneFustella as $c) {
        echo "  {$c->TABLE_NAME}.{$c->COLUMN_NAME} ({$c->DATA_TYPE}" . ($c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : "") . ")\n";
    }
}
echo "\n";

// 3. Se esistono tabelle articoli, mostra le prime 5 righe
$tabelleArticoli = ['Articoli', 'ART', 'ARTArticoli', 'AnagArt', 'MagArticoli'];
foreach ($tabelleArticoli as $nomeTabella) {
    try {
        $righe = $conn->select("SELECT TOP 5 * FROM [{$nomeTabella}]");
        if (!empty($righe)) {
            echo "--- 3. CONTENUTO TOP 5 DA [{$nomeTabella}] ---\n";
            // Mostra nomi colonne
            $colonne = array_keys((array) $righe[0]);
            echo "  Colonne: " . implode(', ', $colonne) . "\n\n";
            foreach ($righe as $i => $riga) {
                echo "  Riga " . ($i + 1) . ":\n";
                foreach ((array) $riga as $col => $val) {
                    if ($val !== null && $val !== '') {
                        echo "    {$col} = {$val}\n";
                    }
                }
                echo "\n";
            }
        }
    } catch (\Exception $e) {
        // Tabella non esiste, skip
    }
}

// 4. Cerca nelle tabelle PRD (produzione) colonne con FS/fust
echo "--- 4. COLONNE IN TABELLE PRD* CONTENENTI 'FS' ---\n";
$colonnePRD = $conn->select("
    SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME LIKE 'PRD%'
      AND (COLUMN_NAME LIKE '%FS%' OR COLUMN_NAME LIKE '%fust%')
    ORDER BY TABLE_NAME, COLUMN_NAME
");
if (empty($colonnePRD)) {
    echo "  Nessuna colonna trovata.\n";
} else {
    foreach ($colonnePRD as $c) {
        echo "  {$c->TABLE_NAME}.{$c->COLUMN_NAME} ({$c->DATA_TYPE}" . ($c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : "") . ")\n";
    }
}
echo "\n";

// 5. Mostra tutte le colonne di PRDDocRighe (dove vengono le fasi)
echo "--- 5. TUTTE LE COLONNE DI PRDDocRighe ---\n";
try {
    $colonnePRDRighe = $conn->select("
        SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'PRDDocRighe'
        ORDER BY ORDINAL_POSITION
    ");
    foreach ($colonnePRDRighe as $c) {
        echo "  {$c->COLUMN_NAME} ({$c->DATA_TYPE}" . ($c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : "") . ")\n";
    }
} catch (\Exception $e) {
    echo "  Errore: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== FINE ESPLORAZIONE ===\n";
