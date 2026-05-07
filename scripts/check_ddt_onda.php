<?php
/**
 * Query Onda SQL Server: trova righe DDT vendita reali.
 * Uso: php scripts\check_ddt_onda.php 0001177
 */
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$ddt = $argv[1] ?? '0001177';
$ddtNum = ltrim($ddt, '0');

echo "Query Onda DB per DDT $ddt (numerico: $ddtNum)\n\n";

// 1. View export comoda (se esiste)
echo "=== View ATTViewExportDDTEmessiFornitore (cerca per num documento) ===\n";
try {
    $cols = DB::connection('onda')->select("
        SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'ATTViewExportDDTEmessiFornitore'
        ORDER BY ORDINAL_POSITION
    ");
    echo "Colonne: " . count($cols) . "\n";
    foreach ($cols as $c) echo "  - {$c->COLUMN_NAME}\n";
} catch (\Throwable $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}

// 2. Trova tutte tabelle ATT* (DDT/Bolle vendita Onda)
echo "\n=== Tutte tabelle ATT* in Onda ===\n";
try {
    $tabelle = DB::connection('onda')->select("
        SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_TYPE = 'BASE TABLE' AND (TABLE_NAME LIKE 'ATT%' OR TABLE_NAME LIKE 'OC_%DDT%')
        ORDER BY TABLE_NAME
    ");
    foreach ($tabelle as $t) echo "  - {$t->TABLE_NAME}\n";
} catch (\Throwable $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}

// 3. Cerca colonne con "NumDoc" + "Vendita" o "DDT"
echo "\n=== Colonne con NumDoc/NumDocumento in tabelle ATT/DDT ===\n";
try {
    $cols = DB::connection('onda')->select("
        SELECT TABLE_NAME, COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE (TABLE_NAME LIKE 'ATT%' OR TABLE_NAME LIKE 'OC_%')
          AND (COLUMN_NAME LIKE '%NumDoc%' OR COLUMN_NAME LIKE '%Numero%')
        ORDER BY TABLE_NAME
    ");
    foreach ($cols as $c) echo "  - {$c->TABLE_NAME}.{$c->COLUMN_NAME}\n";
} catch (\Throwable $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}

// 4. Trova OC_CreazioneAutomaticaDDT (ha DDT nel nome)
echo "\n=== Schema OC_CreazioneAutomaticaDDT ===\n";
try {
    $cols = DB::connection('onda')->select("
        SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'OC_CreazioneAutomaticaDDT'
        ORDER BY ORDINAL_POSITION
    ");
    foreach ($cols as $c) echo "  - {$c->COLUMN_NAME} ({$c->DATA_TYPE})\n";
    echo "\nRow sample (max 3):\n";
    $rows = DB::connection('onda')->select("SELECT TOP 3 * FROM OC_CreazioneAutomaticaDDT WHERE 1=1");
    foreach ($rows as $r) {
        print_r($r);
    }
} catch (\Throwable $e) {
    echo "Errore: " . $e->getMessage() . "\n";
}
