<?php
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Presenze 16-03-2026');

$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$cellStyle = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$warningStyle = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
    'font' => ['bold' => true, 'color' => ['rgb' => 'DC2626']],
];

$sheet->setCellValue('A1', 'Badge');
$sheet->setCellValue('B1', 'Cognome');
$sheet->setCellValue('C1', 'Nome');
$sheet->setCellValue('D1', 'Entrata');
$sheet->setCellValue('E1', 'Note');
$sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

$presenti = [
    ['000659', 'AMOROSO', 'DOMENICO', '06:57'],
    ['000664', 'CRISANTI', 'LORENZO', '08:00'],
    ['000651', 'D ORAZIO', 'MIRKO', '07:51'],
    ['000658', 'FRANCESE', 'FRANCESCO', '07:56'],
    ['000025', 'MARFELLA', 'DOMENICO', '05:48'],
    ['000668', 'MARRONE', 'VINCENZO', '05:48'],
    ['000007', 'MENALE', 'FRANCESCO', '05:55'],
    ['000669', 'MENALE', 'LUIGI', '07:38'],
    ['000032', '???', '???', '05:50', 'Badge 000032 — chi è? (Marco dice NO BADGE per PECORARO)'],
    ['000041', '???', '???', '07:34', 'Badge 000041 — chi è? (vecchio badge MENALE FIORE?)'],
    ['000662', 'PAGANO', 'DIEGO', '06:08'],
    ['000654', 'RAO', 'CIRO', '06:00'],
    ['000044', 'RUSSO', 'MICHELE', '07:48'],
    ['000009', 'SANTORO', 'MARIO', '07:32'],
    ['000039', 'SCARANO', 'GIANGIUSEPPE', '07:38'],
    ['000019', 'SCOTTI', 'FRANCESCO', '07:51'],
    ['000024', 'SORBO', 'LUCA', '06:08'],
    ['000666', 'TORROMACCO', 'GIANNANTONIO', '07:51'],
    ['000667', 'VERDE', 'FRANCESCO', '07:51'],
    ['000660', 'ZAMPELLA', 'ALESSANDRO', '07:36'],
];

$row = 2;
foreach ($presenti as $p) {
    $sheet->setCellValueExplicit("A$row", $p[0], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue("B$row", $p[1]);
    $sheet->setCellValue("C$row", $p[2]);
    $sheet->setCellValue("D$row", $p[3]);
    $sheet->setCellValue("E$row", $p[4] ?? '');

    if ($p[1] === '???') {
        $sheet->getStyle("A$row:E$row")->applyFromArray($warningStyle);
    } else {
        $sheet->getStyle("A$row:E$row")->applyFromArray($cellStyle);
    }
    $row++;
}

$sheet->getColumnDimension('A')->setWidth(12);
$sheet->getColumnDimension('B')->setWidth(22);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->getColumnDimension('D')->setWidth(10);
$sheet->getColumnDimension('E')->setWidth(55);

$outputPath = __DIR__ . '/presenze_oggi_badge_mancanti.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($outputPath);
echo "File salvato: $outputPath\n";
