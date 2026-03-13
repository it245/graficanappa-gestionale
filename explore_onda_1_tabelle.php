<?php
/**
 * Esplorazione Onda - Script 1: Mappa completa delle tabelle
 * Eseguire sul server: php explore_onda_1_tabelle.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESPLORAZIONE DATABASE ONDA ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Elenco completo tabelle
echo "--- 1. TUTTE LE TABELLE ---\n";
$tabelle = DB::connection('onda')->select("
    SELECT TABLE_SCHEMA, TABLE_NAME, TABLE_TYPE
    FROM INFORMATION_SCHEMA.TABLES
    ORDER BY TABLE_SCHEMA, TABLE_NAME
");

$perSchema = [];
foreach ($tabelle as $t) {
    $perSchema[$t->TABLE_SCHEMA][] = $t;
}

foreach ($perSchema as $schema => $tabs) {
    echo "\n[Schema: $schema] — " . count($tabs) . " tabelle\n";
    foreach ($tabs as $t) {
        echo "  {$t->TABLE_NAME} ({$t->TABLE_TYPE})\n";
    }
}

echo "\n\nTotale tabelle: " . count($tabelle) . "\n";

// 2. Conteggio righe per le tabelle principali (prefissi noti)
echo "\n--- 2. CONTEGGIO RIGHE (tabelle principali) ---\n";
$prefissi = ['ATT', 'PRD', 'STD', 'MAG', 'VEN', 'ACQ', 'CON', 'DOC', 'ART', 'CLI', 'FOR', 'FAT'];
$interessanti = [];

foreach ($tabelle as $t) {
    if ($t->TABLE_TYPE !== 'BASE TABLE') continue;
    $nome = $t->TABLE_NAME;
    $match = false;
    foreach ($prefissi as $p) {
        if (stripos($nome, $p) === 0) { $match = true; break; }
    }
    if ($match) $interessanti[] = $nome;
}

foreach ($interessanti as $nome) {
    try {
        $count = DB::connection('onda')->selectOne("SELECT COUNT(*) as cnt FROM [{$nome}]");
        printf("  %-40s %s righe\n", $nome, number_format($count->cnt));
    } catch (\Exception $e) {
        printf("  %-40s ERRORE: %s\n", $nome, $e->getMessage());
    }
}

// 3. Colonne interessanti: cerca fust*, art*, cli*, mat*, peso*, dim*, costo*
echo "\n\n--- 3. COLONNE INTERESSANTI (fust, art, cli, mat, peso, dim, costo, forn) ---\n";
$colonne = DB::connection('onda')->select("
    SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE COLUMN_NAME LIKE '%fust%'
       OR COLUMN_NAME LIKE '%FS%'
       OR COLUMN_NAME LIKE '%peso%'
       OR COLUMN_NAME LIKE '%dimen%'
       OR COLUMN_NAME LIKE '%formato%'
       OR COLUMN_NAME LIKE '%lotto%'
       OR COLUMN_NAME LIKE '%magaz%'
       OR COLUMN_NAME LIKE '%giace%'
       OR COLUMN_NAME LIKE '%forn%'
       OR COLUMN_NAME LIKE '%costo%'
       OR COLUMN_NAME LIKE '%prezzo%'
       OR COLUMN_NAME LIKE '%sconto%'
       OR COLUMN_NAME LIKE '%margine%'
    ORDER BY TABLE_NAME, COLUMN_NAME
");

$perTabella = [];
foreach ($colonne as $c) {
    $perTabella[$c->TABLE_NAME][] = $c;
}

foreach ($perTabella as $tab => $cols) {
    echo "\n  [{$tab}]\n";
    foreach ($cols as $c) {
        $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
        echo "    {$c->COLUMN_NAME} — {$c->DATA_TYPE}{$len}\n";
    }
}

// 4. Tabelle NON ancora usate nel MES (escludendo quelle già importate)
echo "\n\n--- 4. TABELLE GIA' USATE NEL MES ---\n";
$usate = ['ATTDocTeste', 'PRDDocTeste', 'STDAnagrafiche', 'PRDDocFasi', 'PRDDocRighe', 'PRDMacchinari'];
echo "  " . implode(', ', $usate) . "\n";

echo "\n--- 5. TABELLE POTENZIALMENTE UTILI (non ancora usate) ---\n";
$keywords = ['art', 'anag', 'cli', 'forn', 'mag', 'giacen', 'ddt', 'fattur', 'listino', 'prezzo', 'materiale', 'lotto'];
foreach ($tabelle as $t) {
    if ($t->TABLE_TYPE !== 'BASE TABLE') continue;
    if (in_array($t->TABLE_NAME, $usate)) continue;
    $nome = strtolower($t->TABLE_NAME);
    foreach ($keywords as $kw) {
        if (stripos($nome, $kw) !== false) {
            echo "  {$t->TABLE_NAME}\n";
            break;
        }
    }
}

// Salva report
$report = ob_get_contents() ?: '';
// Ricattura l'output
ob_start();
echo $report;
echo "\n\n=== FINE ESPLORAZIONE ===\n";

echo "\nScript completato. Eseguire sul server con: php explore_onda_1_tabelle.php\n";
echo "Per salvare: php explore_onda_1_tabelle.php > storage/report_onda_tabelle.txt\n";
