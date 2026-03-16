<?php
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Anagrafica Badge');

$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$cellStyle = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
];
$inputStyle = [
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
];

$sheet->setCellValue('A1', 'Cognome');
$sheet->setCellValue('B1', 'Nome');
$sheet->setCellValue('C1', 'Badge (num. su tessera)');
$sheet->setCellValue('D1', 'Matricola NetTime');
$sheet->setCellValue('E1', 'Note');
$sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
$sheet->getRowDimension(1)->setRowHeight(30);

$dipendenti = [
    ['AMOROSO', 'DOMENICO', '000659'],
    ['BARBATO', 'RAFFAELE', '000059'],
    ['BORTONE', 'PAOLO', '000058'],
    ['CARDILLO', 'ANTONIO', '000054'],
    ['CARDILLO', 'MARCO', '000045'],
    ['CASTELLANO', 'ANTONIO', '000021'],
    ['CRISANTI', 'LORENZO', '000664'],
    ['D ORAZIO', 'MIRKO', '000651'],
    ['FRANCESE', 'FRANCESCO', '000658'],
    ['GARGIULO', 'VINCENZO', '000653'],
    ['IULIANO', 'PASQUALE', '000051'],
    ['MARFELLA', 'DOMENICO', '000029'],
    ['MARINO', 'LUIGI', '000025'],
    ['MARRONE', 'VINCENZO', '000668'],
    ['MENALE', 'BENITO', '000014'],
    ['MENALE', 'FIORE', '000041'],
    ['MENALE', 'FRANCESCO', '000013'],
    ['MENALE', 'LUIGI', '000669'],
    ['MORMILE', 'COSIMO', '000019'],
    ['PAGANO', 'DIEGO', '000024'],
    ['PECORARO', 'MARCO', '000032'],
    ['RAO', 'CIRO', '000654'],
    ['RUSSO', 'MICHELE', '000044'],
    ['SANTORO', 'MARIO', '000015'],
    ['SCARANO', 'GIANGIUSEPPE', '000039'],
    ['SCOTTI', 'FRANCESCO', '000023'],
    ['SIMONETTI', 'CHRISTIAN', '000046'],
    ['SORBO', 'LUCA', '000027'],
    ['TORROMACCO', 'GIANNANTONIO', '000666'],
    ['TORROMACCO', 'GIUSEPPE', '000011'],
    ['VERDE', 'FRANCESCO', '000667'],
    ['ZAMPELLA', 'ALESSANDRO', '000660'],
    ['???', '???', '000007', '', 'Chi usa questo badge? Entra alle 5:55'],
    ['???', '???', '000009', '', 'Mai usato a marzo - badge attivo?'],
];

$row = 2;
foreach ($dipendenti as $d) {
    $sheet->setCellValue("A$row", $d[0]);
    $sheet->setCellValue("B$row", $d[1]);
    $sheet->setCellValueExplicit("C$row", $d[2], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue("D$row", $d[3] ?? '');
    $sheet->setCellValue("E$row", $d[4] ?? '');
    $sheet->getStyle("A$row:B$row")->applyFromArray($cellStyle);
    $sheet->getStyle("C$row")->applyFromArray($cellStyle);
    $sheet->getStyle("D$row")->applyFromArray($inputStyle);
    $sheet->getStyle("E$row")->applyFromArray($cellStyle);
    if ($d[0] === '???') {
        $sheet->getStyle("A$row:E$row")->getFont()->getColor()->setRGB('DC2626');
        $sheet->getStyle("A$row:E$row")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');
    }
    $row++;
}

$sheet->getColumnDimension('A')->setWidth(22);
$sheet->getColumnDimension('B')->setWidth(20);
$sheet->getColumnDimension('C')->setWidth(24);
$sheet->getColumnDimension('D')->setWidth(22);
$sheet->getColumnDimension('E')->setWidth(40);

$outputPath = __DIR__ . '/anagrafica_badge_matricole.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($outputPath);
echo "File salvato: $outputPath\n";
