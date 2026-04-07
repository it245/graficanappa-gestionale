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

// Mostra tutte le righe con RIF. ORD. compilato (colonna G)
echo "\n=== RIGHE CON RIF. ORD. MAXTRIS (Col G non vuota) ===\n";
$found = 0;
foreach ($sheet->getRowIterator(2) as $row) {
    $r = $row->getRowIndex();
    $f = trim($sheet->getCell("F$r")->getValue() ?? '');
    $g = trim($sheet->getCell("G$r")->getValue() ?? '');
    $b = trim($sheet->getCell("B$r")->getValue() ?? '');
    if ($g !== '') {
        echo "  Riga $r: F(commessa)=[$f] G(rif)=[$g] B(desc)=[$b]\n";
        $found++;
    }
}
echo "Trovate: $found\n";
