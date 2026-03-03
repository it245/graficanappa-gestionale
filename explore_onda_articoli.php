<?php
/**
 * Esplora Onda: Struttura > Anagrafiche > Anagrafiche Articoli
 * Uso: php explore_onda_articoli.php [codart]
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$codArt = $argv[1] ?? null;
$conn = DB::connection('onda');

echo "=== ONDA: Anagrafiche Articoli ===\n\n";

// 1. Trova la tabella anagrafiche articoli
echo "--- Tabelle anagrafiche/articoli ---\n";
$tabelle = $conn->select("
    SELECT TABLE_NAME
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_TYPE = 'BASE TABLE'
      AND (TABLE_NAME LIKE '%Artic%' OR TABLE_NAME LIKE '%AnaArt%'
           OR TABLE_NAME LIKE '%AnagArt%' OR TABLE_NAME LIKE '%MagArt%'
           OR TABLE_NAME LIKE 'STDArt%' OR TABLE_NAME LIKE 'ARTArt%')
    ORDER BY TABLE_NAME
");
foreach ($tabelle as $t) {
    $count = $conn->selectOne("SELECT COUNT(*) as cnt FROM [{$t->TABLE_NAME}]");
    echo "  {$t->TABLE_NAME} ({$count->cnt} righe)\n";
}

// 2. Cerca anche tabelle STDAnag*
echo "\n--- Tabelle STDAnag* ---\n";
$tabelleAnag = $conn->select("
    SELECT TABLE_NAME
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_NAME LIKE 'STDAnag%'
    ORDER BY TABLE_NAME
");
foreach ($tabelleAnag as $t) {
    $count = $conn->selectOne("SELECT COUNT(*) as cnt FROM [{$t->TABLE_NAME}]");
    echo "  {$t->TABLE_NAME} ({$count->cnt} righe)\n";
}

// 3. Tutte le tabelle del DB (per trovare quella giusta)
echo "\n--- TUTTE le tabelle BASE TABLE ---\n";
$tutte = $conn->select("
    SELECT TABLE_NAME
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_TYPE = 'BASE TABLE'
    ORDER BY TABLE_NAME
");
foreach ($tutte as $t) {
    echo "  {$t->TABLE_NAME}\n";
}

// 4. Se c'è un codart, cerca in PRDDocTeste
if ($codArt) {
    echo "\n=== Ricerca articolo: {$codArt} ===\n";
    $righe = $conn->select("
        SELECT TOP 5 p.*
        FROM PRDDocTeste p
        WHERE p.CodArt LIKE ?
    ", ['%' . $codArt . '%']);

    if (empty($righe)) {
        echo "Nessun risultato in PRDDocTeste.\n";
    } else {
        foreach ($righe as $r) {
            $arr = (array) $r;
            echo "\n";
            foreach ($arr as $k => $v) {
                if ($v !== null && $v !== '') {
                    echo "  {$k}: {$v}\n";
                }
            }
        }
    }
}

echo "\n=== FINE ===\n";
