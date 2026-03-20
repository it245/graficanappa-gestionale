<?php
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$spreadsheet = new Spreadsheet();

// ===================== FOGLIO 1: Badge sconosciuti =====================
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Badge sconosciuti');

$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DC2626']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$cellStyle = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
];

$sheet->setCellValue('A1', 'Badge');
$sheet->setCellValue('B1', 'Note');
$sheet->setCellValue('C1', 'Chi lo usa?');
$sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

$sconosciuti = [
    ['000007', 'Timbra tutti i giorni — chi lo usa realmente?'],
    ['000009', 'Sconosciuto, mai registrato'],
    ['000659', 'Sconosciuto, mai registrato'],
];
$row = 2;
foreach ($sconosciuti as $s) {
    $sheet->setCellValueExplicit("A$row", $s[0], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue("B$row", $s[1]);
    $sheet->setCellValue("C$row", '');
    $sheet->getStyle("A$row:C$row")->applyFromArray($cellStyle);
    $row++;
}
$sheet->getColumnDimension('A')->setWidth(14);
$sheet->getColumnDimension('B')->setWidth(45);
$sheet->getColumnDimension('C')->setWidth(25);

// ===================== FOGLIO 2: Badge doppi =====================
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Badge doppi');

$headerStyle2 = $headerStyle;
$headerStyle2['fill']['startColor']['rgb'] = 'F59E0B';
$headerStyle2['font']['color']['rgb'] = '000000';

$sheet2->setCellValue('A1', '#');
$sheet2->setCellValue('B1', 'Persona');
$sheet2->setCellValue('C1', 'Badge 1');
$sheet2->setCellValue('D1', 'Badge 2');
$sheet2->setCellValue('E1', 'Quale usa?');
$sheet2->getStyle('A1:E1')->applyFromArray($headerStyle2);

$doppi = [
    ['BARBATO RAFFAELE', '000059', '000064'],
    ['BORTONE PAOLO', '000058', '000061'],
    ['CARDILLO MARCO', '000045', '000049'],
    ['CRISANTI LORENZO', '000016', '000664'],
    ["D'ORAZIO MIRKO", '000042', '000651'],
    ['FRANCESE FRANCESCO', '000017', '000658'],
    ['GARGIULO VINCENZO', '000050', '000653'],
    ['IULIANO PASQUALE', '000051', '000052'],
    ['MARRONE VINCENZO', '000076', '000668'],
    ['MENALE LUIGI', '000018', '000669'],
    ['PAGANO DIEGO', '000024', '000662'],
    ['RAO CIRO', '000038', '000654'],
    ['RUSSO MICHELE', '000044', '000048'],
    ['SCARANO GIANGIUSEPPE', '000001', '000039'],
    ['TORROMACCO GIANNANTONIO', '000060', '000666'],
    ['VERDE FRANCESCO', '000035', '000667'],
    ['ZAMPELLA ALESSANDRO', '000040', '000660'],
];

$row = 2;
foreach ($doppi as $i => $d) {
    $sheet2->setCellValue("A$row", $i + 1);
    $sheet2->setCellValue("B$row", $d[0]);
    $sheet2->setCellValueExplicit("C$row", $d[1], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet2->setCellValueExplicit("D$row", $d[2], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet2->setCellValue("E$row", '');
    $sheet2->getStyle("A$row:E$row")->applyFromArray($cellStyle);
    $row++;
}
$sheet2->getColumnDimension('A')->setWidth(5);
$sheet2->getColumnDimension('B')->setWidth(30);
$sheet2->getColumnDimension('C')->setWidth(14);
$sheet2->getColumnDimension('D')->setWidth(14);
$sheet2->getColumnDimension('E')->setWidth(25);

// ===================== FOGLIO 3: Badge singoli =====================
$sheet3 = $spreadsheet->createSheet();
$sheet3->setTitle('Badge singoli');

$headerStyle3 = $headerStyle;
$headerStyle3['fill']['startColor']['rgb'] = '059669';

$sheet3->setCellValue('A1', 'Badge');
$sheet3->setCellValue('B1', 'Persona');
$sheet3->setCellValue('C1', 'Corretto?');
$sheet3->getStyle('A1:C1')->applyFromArray($headerStyle3);

$singoli = [
    ['000054', 'CARDILLO ANTONIO'],
    ['000021', 'CASTELLANO ANTONIO'],
    ['000029', 'MARFELLA DOMENICO'],
    ['000025', 'MARINO LUIGI'],
    ['000014', 'MENALE BENITO'],
    ['000041', 'MENALE FIORE'],
    ['000013', 'MENALE FRANCESCO'],
    ['000019', 'MORMILE COSIMO'],
    ['000032', 'PECORARO MARCO'],
    ['000015', 'SANTORO MARIO'],
    ['000023', 'SCOTTI FRANCESCO'],
    ['000046', 'SIMONETTI CHRISTIAN'],
    ['000027', 'SORBO LUCA'],
    ['000011', 'TORROMACCO GIUSEPPE'],
];

$row = 2;
foreach ($singoli as $s) {
    $sheet3->setCellValueExplicit("A$row", $s[0], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet3->setCellValue("B$row", $s[1]);
    $sheet3->setCellValue("C$row", '');
    $sheet3->getStyle("A$row:C$row")->applyFromArray($cellStyle);
    $row++;
}
$sheet3->getColumnDimension('A')->setWidth(14);
$sheet3->getColumnDimension('B')->setWidth(30);
$sheet3->getColumnDimension('C')->setWidth(15);

// Salva
$outputPath = __DIR__ . '/verifica_badge_presenze.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($outputPath);
echo "File salvato: $outputPath\n";
