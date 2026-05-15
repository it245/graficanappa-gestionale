<?php
/**
 * Dump SCHEMA COMPLETO Onda (no dati) → docs/SCHEMA_ONDA_COMPLETO.md
 * Solo INFORMATION_SCHEMA + sys.partitions: read-only, sicuro.
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$inizio = microtime(true);

echo "Estrazione schema...\n";

// Lista tabelle + conteggio righe (sys.partitions = veloce, no full scan)
$tabelle = DB::connection('onda')->select("
    SELECT
        t.TABLE_SCHEMA AS schema_name,
        t.TABLE_NAME AS table_name,
        COALESCE(SUM(p.rows), 0) AS row_count
    FROM INFORMATION_SCHEMA.TABLES t
    LEFT JOIN sys.tables st ON st.name = t.TABLE_NAME
    LEFT JOIN sys.partitions p ON p.object_id = st.object_id AND p.index_id IN (0,1)
    WHERE t.TABLE_TYPE = 'BASE TABLE'
    GROUP BY t.TABLE_SCHEMA, t.TABLE_NAME
    ORDER BY t.TABLE_NAME
");

echo "Trovate " . count($tabelle) . " tabelle.\n";

// Tutte le colonne in un'unica query (più veloce di N query)
$colonne = DB::connection('onda')->select("
    SELECT
        TABLE_NAME, ORDINAL_POSITION, COLUMN_NAME,
        DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS
    ORDER BY TABLE_NAME, ORDINAL_POSITION
");

// Indicizza colonne per tabella
$colByTable = [];
foreach ($colonne as $c) {
    $colByTable[$c->TABLE_NAME][] = $c;
}

// Raggruppa tabelle per prefisso
function prefisso(string $nome): string {
    if (preg_match('/^([A-Z]+)/', $nome, $m)) {
        $p = $m[1];
        // Mappa prefissi noti
        $mapping = [
            'ATT' => 'ATT - Vendite/Ordini',
            'PAS' => 'PAS - Acquisti',
            'PRD' => 'PRD - Produzione',
            'MAG' => 'MAG - Magazzino',
            'COG' => 'COG - Contabilità',
            'STD' => 'STD - Anagrafiche base',
            'OC'  => 'OC - Cartotecnica',
            'TEL' => 'TEL - Telematici/FE',
            'ANA' => 'ANA - Contabilità analitica',
            'CSP' => 'CSP - Cespiti',
            'CRM' => 'CRM',
        ];
        foreach ($mapping as $pre => $label) {
            if (str_starts_with($nome, $pre)) return $label;
        }
    }
    return 'Altri';
}

$grouped = [];
foreach ($tabelle as $t) {
    $grouped[prefisso($t->table_name)][] = $t;
}
ksort($grouped);

// Output Markdown
$md  = "# Schema Completo Database Onda\n\n";
$md .= "Generato: " . date('Y-m-d H:i:s') . " | Server: 192.168.1.253\n\n";
$md .= "**Totale tabelle**: " . count($tabelle) . "  \n";
$md .= "**Totale colonne**: " . count($colonne) . "  \n";
$md .= "**Aree funzionali**: " . count($grouped) . "\n\n";

$md .= "## Indice\n\n";
foreach ($grouped as $area => $list) {
    $anchor = strtolower(str_replace([' ', '/', '-'], '-', $area));
    $md .= "- [$area](#" . preg_replace('/[^a-z0-9-]/', '', $anchor) . ") — " . count($list) . " tabelle\n";
}
$md .= "\n---\n\n";

foreach ($grouped as $area => $list) {
    $md .= "## $area\n\n";
    $md .= "Tabelle: **" . count($list) . "**  \n\n";

    foreach ($list as $t) {
        $cols = $colByTable[$t->table_name] ?? [];
        $rowCount = number_format((int)$t->row_count, 0, ',', '.');
        $md .= "### {$t->table_name}\n";
        $md .= "_Righe: $rowCount | Colonne: " . count($cols) . "_\n\n";
        if (!empty($cols)) {
            $md .= "| # | Colonna | Tipo | Lung. | Null |\n";
            $md .= "|---|---------|------|-------|------|\n";
            foreach ($cols as $c) {
                $tipo = $c->DATA_TYPE;
                $lung = $c->CHARACTER_MAXIMUM_LENGTH ?: ($c->NUMERIC_PRECISION ?: '');
                $null = $c->IS_NULLABLE === 'YES' ? '✓' : '';
                $md .= "| {$c->ORDINAL_POSITION} | `{$c->COLUMN_NAME}` | $tipo | $lung | $null |\n";
            }
        }
        $md .= "\n";
    }
}

$outFile = __DIR__ . '/docs/SCHEMA_ONDA_COMPLETO.md';
file_put_contents($outFile, $md);

$elapsed = round(microtime(true) - $inizio, 1);
$sizeKB = round(filesize($outFile) / 1024, 1);
echo "OK. File: $outFile ({$sizeKB} KB) | Tempo: {$elapsed}s\n";
