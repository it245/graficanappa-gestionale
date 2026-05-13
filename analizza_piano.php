<?php
error_reporting(E_ERROR | E_PARSE);
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'C:\Users\Giovanni\Downloads\piano_produzione_2026-05-12.xlsx';
if (!is_file($file)) { echo "File non trovato\n"; exit(1); }

$ss = IOFactory::load($file);
foreach ($ss->getAllSheets() as $sheet) {
    echo "\n=== Sheet: " . $sheet->getTitle() . " ===\n";
    $rows = $sheet->toArray();
    echo "Rows: " . count($rows) . "\n";
    if (empty($rows)) continue;
    // Header
    $hdr = $rows[0] ?? [];
    echo "Header: " . implode(' | ', array_slice($hdr, 0, 15)) . "\n";
    // Prime 3 righe dati
    for ($i = 1; $i < min(4, count($rows)); $i++) {
        $r = $rows[$i];
        echo "Row $i: " . implode(' | ', array_map(fn($v) => is_string($v) ? substr($v,0,25) : $v, array_slice($r, 0, 15))) . "\n";
    }
    echo "\n";
}
