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
$sheet->setCellValue('B1', 'Codice FS');
$sheet->setCellValue('C1', 'Articolo');
$sheet->setCellValue('D1', 'Qta Cliché');
$sheet->setCellValue('E1', 'Articoli ulteriori (stesso cliché)');

$sheet->getStyle('A1:E1')->applyFromArray([
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
    $sheet->setCellValue("D$row", '');
    $sheet->setCellValue("E$row", '');

    $row++;
}

// Bordi
$sheet->getStyle("A2:E$row")->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

$sheet->getColumnDimension('A')->setWidth(18);
$sheet->getColumnDimension('B')->setWidth(18);
$sheet->getColumnDimension('C')->setWidth(55);
$sheet->getColumnDimension('D')->setWidth(18);
$sheet->getColumnDimension('E')->setWidth(65);

// Altezza righe: più spazio per scrivere a penna
for ($r = 2; $r < $row; $r++) {
    $sheet->getRowDimension($r)->setRowHeight(35);
}
$sheet->getRowDimension(1)->setRowHeight(25);

$sheet->getStyle("A2:E$row")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getStyle("A2:A$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("B2:B$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("D2:D$row")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("A2:A$row")->getFont()->setSize(14)->setBold(true);

$percorso = __DIR__ . '/storage/Numerazione_Clice_2000_2300.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($percorso);

echo "File: $percorso\n";
echo "Righe: 2000-2300 (301 righe)\n";
