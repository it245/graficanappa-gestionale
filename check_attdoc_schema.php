<?php
/**
 * Discovery schema ATTDocRighe + dump record per commessa.
 * Usage: php check_attdoc_schema.php 0067339-26
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = $argv[1] ?? '0067339-26';
$onda = DB::connection('onda');

echo "\n=== ATTDocRighe schema + record commessa {$commessa} ===\n\n";

// 1) Schema
echo "--- 1. Colonne ATTDocRighe ---\n";
try {
    $cols = $onda->select("
        SELECT COLUMN_NAME, DATA_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'ATTDocRighe'
        ORDER BY ORDINAL_POSITION
    ");
    foreach ($cols as $c) echo "  " . $c->COLUMN_NAME . " (" . $c->DATA_TYPE . ")\n";
} catch (\Exception $e) {
    echo "  ERR: " . $e->getMessage() . "\n";
}

// 2) Tutti record commessa
echo "\n--- 2. ATTDocRighe per commessa {$commessa} ---\n";
try {
    $rows = $onda->select("SELECT * FROM ATTDocRighe WHERE CodCommessa = ? ORDER BY IdRiga", [$commessa]);
    if (empty($rows)) {
        echo "  Nessuna riga.\n";
    } else {
        foreach ($rows as $i => $r) {
            echo "\n  RIGA #" . ($i + 1) . ":\n";
            foreach ((array) $r as $col => $val) {
                if ($val !== null && $val !== '' && !is_object($val)) {
                    $str = is_scalar($val) ? $val : json_encode($val);
                    if (strlen($str) > 80) $str = substr($str, 0, 80) . '...';
                    echo "    {$col} = {$str}\n";
                }
            }
        }
        echo "\n  Totale: " . count($rows) . "\n";
    }
} catch (\Exception $e) {
    echo "  ERR: " . $e->getMessage() . "\n";
}

echo "\n";
