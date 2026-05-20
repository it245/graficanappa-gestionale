<?php
require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$files = [
    'C:\Users\Giovanni\Downloads\GN_Materie_Prime.xlsx',
    'C:\Users\Giovanni\Downloads\GN_Database_Costi_Completo.xlsx',
];

foreach ($files as $file) {
    echo "\n\n========================================\n";
    echo "FILE: " . basename($file) . "\n";
    echo "========================================\n";

    $reader = IOFactory::createReaderForFile($file);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($file);

    foreach ($spreadsheet->getSheetNames() as $sheetName) {
        $sheet = $spreadsheet->getSheetByName($sheetName);
        $rows = $sheet->toArray(null, true, true, false);

        // Skip foglio vuoto
        $hasData = false;
        foreach ($rows as $r) {
            foreach ($r as $c) if ($c !== null && $c !== '') { $hasData = true; break 2; }
        }
        if (!$hasData) continue;

        echo "\n----- SHEET: {$sheetName} (" . count($rows) . " righe) -----\n";

        $maxRows = min(count($rows), 50); // limita output
        for ($i = 0; $i < $maxRows; $i++) {
            $row = $rows[$i];
            // Skip righe completamente vuote
            $empty = true;
            foreach ($row as $c) if ($c !== null && $c !== '') { $empty = false; break; }
            if ($empty) continue;
            // Formatta
            $cells = array_map(fn($c) => is_numeric($c) ? round((float)$c, 4) : (string)($c ?? ''), $row);
            // Tronca celle troppo lunghe
            $cells = array_map(fn($c) => mb_strlen((string)$c) > 60 ? mb_substr((string)$c, 0, 57) . '...' : $c, $cells);
            // Trim trailing empty
            while (count($cells) > 0 && ($cells[count($cells)-1] === '' || $cells[count($cells)-1] === null)) {
                array_pop($cells);
            }
            if (empty($cells)) continue;
            echo "  " . str_pad((string)($i+1), 3, ' ', STR_PAD_LEFT) . " | " . implode(' | ', $cells) . "\n";
        }
        if (count($rows) > 50) echo "  ... (altre " . (count($rows)-50) . " righe troncate)\n";
    }
}
