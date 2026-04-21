<?php
/**
 * Esplora schema Onda per trovare colonne contenenti "scart" o simili.
 * Uso: php check_onda_scarti_campi.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Cerco colonne con 'scart' nel nome ===\n";
$cols = DB::connection('onda')->select("
    SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE COLUMN_NAME LIKE '%scart%' OR COLUMN_NAME LIKE '%Scart%' OR COLUMN_NAME LIKE '%SCART%'
    ORDER BY TABLE_NAME, COLUMN_NAME
");
foreach ($cols as $c) {
    echo sprintf("  %-40s %-30s %s\n", $c->TABLE_NAME, $c->COLUMN_NAME, $c->DATA_TYPE);
}

echo "\n=== Cerco colonne in tabelle ATT*/PRD*/OC_* con termini correlati (resa, waste, mag, perdita) ===\n";
$cols2 = DB::connection('onda')->select("
    SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE (TABLE_NAME LIKE 'ATT%' OR TABLE_NAME LIKE 'PRD%' OR TABLE_NAME LIKE 'OC_%')
      AND (COLUMN_NAME LIKE '%waste%' OR COLUMN_NAME LIKE '%Perdit%' OR COLUMN_NAME LIKE '%Magg%' OR COLUMN_NAME LIKE '%Extra%')
    ORDER BY TABLE_NAME, COLUMN_NAME
");
foreach ($cols2 as $c) {
    echo sprintf("  %-40s %-30s %s\n", $c->TABLE_NAME, $c->COLUMN_NAME, $c->DATA_TYPE);
}

echo "\n=== Struttura OC_ATTDocRigheExt (ext articoli lavorazione) ===\n";
$extCols = DB::connection('onda')->select("
    SELECT COLUMN_NAME, DATA_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'OC_ATTDocRigheExt'
    ORDER BY ORDINAL_POSITION
");
foreach ($extCols as $c) {
    echo sprintf("  %-35s %s\n", $c->COLUMN_NAME, $c->DATA_TYPE);
}

echo "\n=== Esempio dati per commessa 66575 (se esiste) ===\n";
$sample = DB::connection('onda')->select("
    SELECT TOP 5 *
    FROM OC_ATTDocRigheExt ext
    INNER JOIN ATTDocTeste t ON ext.OC_IdDoc = t.IdDoc
    WHERE t.CodCommessa LIKE '%66575%'
");
foreach ($sample as $r) {
    print_r((array) $r);
    echo "---\n";
}
