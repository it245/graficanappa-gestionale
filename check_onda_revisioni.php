<?php
/**
 * Verifica revisioni PRDDoc per commessa — schema discovery + dump.
 * Usage: php check_onda_revisioni.php 0067339-26
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = $argv[1] ?? '0067339-26';
$onda = DB::connection('onda');

echo "\n=== Commessa: {$commessa} — revisioni Onda ===\n\n";

// 0) Schema PRDDocTeste
echo "--- 0. Colonne PRDDocTeste ---\n";
try {
    $cols = $onda->select("
        SELECT COLUMN_NAME, DATA_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'PRDDocTeste'
        ORDER BY ORDINAL_POSITION
    ");
    foreach ($cols as $c) {
        echo "  " . $c->COLUMN_NAME . " (" . $c->DATA_TYPE . ")\n";
    }
} catch (\Exception $e) {
    echo "  ERRORE: " . $e->getMessage() . "\n";
}

// 1) PRDDocTeste — dump tutto record per commessa
echo "\n--- 1. PRDDocTeste record commessa {$commessa} ---\n";
try {
    $teste = $onda->select("
        SELECT * FROM PRDDocTeste WHERE CodCommessa = ?
    ", [$commessa]);

    if (empty($teste)) {
        echo "  Nessun record.\n";
    } else {
        foreach ($teste as $i => $t) {
            echo "  RECORD #" . ($i + 1) . ":\n";
            foreach ((array) $t as $col => $val) {
                if ($val !== null && $val !== '') {
                    echo "    {$col} = " . (is_scalar($val) ? $val : json_encode($val)) . "\n";
                }
            }
            echo "\n";
        }
        echo "  Totale: " . count($teste) . "\n";
    }
} catch (\Exception $e) {
    echo "  ERRORE: " . $e->getMessage() . "\n";
}

// 2) Per ogni IdDoc, mostra PRDDocFasi
if (!empty($teste)) {
    echo "\n--- 2. PRDDocFasi per ogni IdDoc ---\n";
    foreach ($teste as $t) {
        echo "\n  >> IdDoc={$t->IdDoc}:\n";
        try {
            $fasi = $onda->select("
                SELECT CodFase, CodMacchina, QtaDaLavorare, CodUnMis
                FROM PRDDocFasi
                WHERE IdDoc = ?
                ORDER BY CodFase
            ", [$t->IdDoc]);
            if (empty($fasi)) {
                echo "       Nessuna fase.\n";
            } else {
                foreach ($fasi as $f) {
                    echo sprintf("       %-32s Qta=%-10s %s\n",
                        $f->CodFase, $f->QtaDaLavorare, $f->CodUnMis ?? '');
                }
            }
        } catch (\Exception $e) {
            echo "       ERRORE: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n";
