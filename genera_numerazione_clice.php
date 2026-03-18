<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Numerazione Cliché');

// Header
$sheet->setCellValue('A1', 'N° Cliché');
$sheet->setCellValue('B1', 'Articolo');
$sheet->setCellValue('C1', 'Qta Cliché');

$sheet->getStyle('A1:C1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D11317']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

// Righe da 2000 a 2300
$row = 2;
for ($i = 2000; $i <= 2300; $i++) {
    $sheet->setCellValue("A$row", $i);
    $sheet->setCellValue("B$row", '');
    $sheet->setCellValue("C$row", '');

    // Sfondo giallo sulle celle da compilare
    $sheet->getStyle("B$row:C$row")->applyFromArray([
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFCC']],
    ]);
    $row++;
}

// Bordi
$sheet->getStyle("A2:C$row")->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

// Larghezze colonne
$sheet->getColumnDimension('A')->setWidth(14);
$sheet->getColumnDimension('B')->setWidth(50);
$sheet->getColumnDimension('C')->setWidth(14);

// Allineamento numeri
$sheet->getStyle("A2:A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("C2:C$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$percorso = __DIR__ . '/storage/Numerazione_Clice_2000_2300.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($percorso);

echo "File: $percorso\n";
echo "Righe: 2000-2300 (301 righe)\n";
