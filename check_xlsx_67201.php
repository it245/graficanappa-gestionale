<?php
require __DIR__.'/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'C:\condivisa\mes\ORDINE ASTUCCI.xlsx';
echo "File: $file\n";
echo "Modificato: " . date('Y-m-d H:i:s', filemtime($file)) . "\n";
echo "Size: " . round(filesize($file)/1024, 1) . " KB\n\n";

$sp = IOFactory::load($file);
$sheet = $sp->getActiveSheet();
$highestRow = $sheet->getHighestRow();

echo "Righe totali: $highestRow\n\n";

echo "=== Header (row 1) ===\n";
foreach (range('A', 'I') as $col) {
    echo "  $col: " . trim((string)$sheet->getCell($col . '1')->getValue()) . "\n";
}

echo "\n=== Righe con 67201 o 67203 ===\n";
$found = 0;
foreach ($sheet->getRowIterator(2) as $row) {
    $r = $row->getRowIndex();
    $commessa = trim((string)$sheet->getCell("F$r")->getValue());
    $desc = trim((string)$sheet->getCell("B$r")->getValue());
    $rif = trim((string)$sheet->getCell("G$r")->getValue());
    if (str_contains($commessa, '67201') || str_contains($commessa, '67203')) {
        echo "  Row $r: commessa='$commessa' | desc='$desc' | RIF='$rif'\n";
        $found++;
    }
}
if ($found === 0) echo "  NESSUNA RIGA TROVATA per 67201/67203\n";

echo "\n=== Cliente Italiana Confetti nel file? ===\n";
$foundIC = 0;
foreach ($sheet->getRowIterator(2) as $row) {
    $r = $row->getRowIndex();
    foreach (range('A', 'I') as $col) {
        $v = (string)$sheet->getCell($col . $r)->getValue();
        if (stripos($v, 'italiana') !== false || stripos($v, 'confetti') !== false) {
            echo "  Row $r col $col: $v\n";
            $foundIC++;
            break;
        }
    }
    if ($foundIC > 5) break;
}
if ($foundIC === 0) echo "  Nessun riferimento ITALIANA CONFETTI\n";

echo "\n=== Tutte le commesse uniche ===\n";
$commesse = [];
foreach ($sheet->getRowIterator(2) as $row) {
    $r = $row->getRowIndex();
    $commessa = trim((string)$sheet->getCell("F$r")->getValue());
    if ($commessa) $commesse[$commessa] = true;
}
echo "  Tot: " . count($commesse) . "\n";
echo "  Lista: " . implode(', ', array_slice(array_keys($commesse), 0, 50)) . "\n";
