<?php
// Esplora struttura Excel cliché: intestazioni + prime 20 righe
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? 'C:\\Users\\Giovanni\\Downloads\\Numerazione_Clice_2000_2300 1 (1) (1).xlsx';

if (!file_exists($path)) {
    die("File non trovato: $path\n");
}

error_reporting(E_ALL & ~E_DEPRECATED);

$ss = IOFactory::load($path);
foreach ($ss->getAllSheets() as $sheet) {
    echo "\n=== SHEET: {$sheet->getTitle()} ===\n";
    $rows = $sheet->toArray(null, false, false, true);
    $n = 0;
    foreach ($rows as $rowIdx => $row) {
        $n++;
        if ($n > 25) break;
        $parts = [];
        foreach ($row as $col => $val) {
            if ($val === null || $val === '') continue;
            $parts[] = "[$col] " . trim((string)$val);
        }
        echo "R{$rowIdx}: " . implode(' | ', $parts) . "\n";
    }
    echo "... (tot righe: " . count($rows) . ")\n";
}
