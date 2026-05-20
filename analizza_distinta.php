<?php
require __DIR__.'/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
$files = [
    'C:\Users\Giovanni\Downloads\Distinta_Base_Parametrica.xlsx',
    'C:\Users\Giovanni\Downloads\Distinta_Base_Astucci_30000_v2.xlsx',
];
foreach ($files as $file) {
echo "\n\n##############################################\n# " . basename($file) . "\n##############################################\n";
if (!is_file($file)) { echo "(file non trovato)\n"; continue; }
$reader = IOFactory::createReaderForFile($file);
$reader->setReadDataOnly(true);
$ss = $reader->load($file);
foreach ($ss->getSheetNames() as $name) {
    echo "===== {$name} =====\n";
    $sheet = $ss->getSheetByName($name);
    $rows = $sheet->toArray(null, true, true, false);
    foreach ($rows as $i => $row) {
        $empty = true;
        foreach ($row as $c) if ($c !== null && $c !== '') { $empty = false; break; }
        if ($empty) continue;
        $cells = array_map(fn($c) => is_numeric($c) ? round((float)$c, 4) : (string)($c ?? ''), $row);
        while (count($cells) > 0 && ($cells[count($cells)-1] === '' || $cells[count($cells)-1] === null)) array_pop($cells);
        echo "  " . ($i+1) . " | " . implode(' | ', $cells) . "\n";
    }
    echo "\n";
}
}
