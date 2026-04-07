<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

error_reporting(E_ALL & ~E_DEPRECATED);

$excelPath = env('EXCEL_SYNC_PATH', storage_path('app/excel_sync'));
$file = rtrim($excelPath, '/\\') . DIRECTORY_SEPARATOR . 'ORDINE ASTUCCI.xlsx';

echo "=== FILE: $file ===\n";
echo "Esiste: " . (file_exists($file) ? 'SI' : 'NO') . "\n\n";

if (!file_exists($file)) exit;

$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();

// Mostra header (riga 1)
echo "=== HEADER ===\n";
for ($col = 'A'; $col <= 'H'; $col++) {
    echo "  $col: " . $sheet->getCell($col . '1')->getValue() . "\n";
}

// Mostra prime 10 righe dati
echo "\n=== PRIME 10 RIGHE (Col A = commessa?, Col G = rif?) ===\n";
for ($row = 2; $row <= 11; $row++) {
    $a = $sheet->getCell("A$row")->getValue();
    $b = $sheet->getCell("B$row")->getValue();
    $g = $sheet->getCell("G$row")->getValue();
    echo "  Riga $row: A=[$a] B=[$b] G=[$g]\n";
}

// Cerca commesse del DDT 860 (66489, 66718) in qualsiasi colonna
echo "\n=== CERCA COMMESSE DDT 860 ===\n";
$cercate = ['66489', '66718'];
foreach ($sheet->getRowIterator(2) as $row) {
    $r = $row->getRowIndex();
    $riga = '';
    for ($col = 'A'; $col <= 'H'; $col++) {
        $val = trim($sheet->getCell("$col$r")->getValue() ?? '');
        $riga .= " $col=[$val]";
    }
    foreach ($cercate as $c) {
        if (str_contains($riga, $c)) {
            echo "  Riga $r: $riga\n";
        }
    }
}

// Mostra anche le righe con RIF compilato vicino a quelle commesse
echo "\n=== ULTIME 20 RIGHE CON RIF. ORD. ===\n";
$conRif = [];
foreach ($sheet->getRowIterator(2) as $row) {
    $r = $row->getRowIndex();
    $f = trim($sheet->getCell("F$r")->getValue() ?? '');
    $g = trim($sheet->getCell("G$r")->getValue() ?? '');
    $b = trim($sheet->getCell("B$r")->getValue() ?? '');
    if ($g !== '') {
        $conRif[] = "  Riga $r: F=[$f] G=[$g] B=[$b]";
    }
}
foreach (array_slice($conRif, -20) as $line) {
    echo "$line\n";
}
