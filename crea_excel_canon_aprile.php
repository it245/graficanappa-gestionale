<?php

require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Aprile 2026');

$sheet->getColumnDimension('A')->setWidth(28);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(15);

$sheet->setCellValue('A1', 'CONTATORI CANON imagePRESS V900');
$sheet->mergeCells('A1:D1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2C3E50');
$sheet->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');

$sheet->setCellValue('A2', 'GRAFICA NAPPA S.R.L. — Periodo: 01/04/2026 — 30/04/2026');
$sheet->mergeCells('A2:D2');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A2')->getFont()->setItalic(true);

$sheet->setCellValue('A4', 'Categoria');
$sheet->setCellValue('B4', 'MES (nostri)');
$sheet->setCellValue('C4', 'Canon (loro)');
$sheet->setCellValue('D4', 'Differenza');

$sheet->getStyle('A4:D4')->getFont()->setBold(true);
$sheet->getStyle('A4:D4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('3498DB');
$sheet->getStyle('A4:D4')->getFont()->getColor()->setRGB('FFFFFF');
$sheet->getStyle('A4:D4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$rows = [
    ['B/N A4 (nero piccolo)',     1003,  1002],
    ['Colore A4 (colore piccolo)', 24707, 24706],
    ['B/N A3 (nero grande)',       3327,  3326],
    ['Colore A3 (colore grande)',  65459, 65458],
    ['Banner (foglio lungo)',      1210,  1210],
];

$riga = 5;
$totaleNoi = 0;
$totaleLoro = 0;
foreach ($rows as $r) {
    $sheet->setCellValue("A{$riga}", $r[0]);
    $sheet->setCellValue("B{$riga}", $r[1]);
    $sheet->setCellValue("C{$riga}", $r[2]);
    $sheet->setCellValue("D{$riga}", $r[1] - $r[2]);

    $diff = $r[1] - $r[2];
    if ($diff !== 0) {
        $color = $diff > 0 ? 'E74C3C' : 'F39C12';
        $sheet->getStyle("D{$riga}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);
        $sheet->getStyle("D{$riga}")->getFont()->getColor()->setRGB('FFFFFF');
    }

    $totaleNoi += $r[1];
    $totaleLoro += $r[2];
    $riga++;
}

$sheet->setCellValue("A{$riga}", 'TOTALE');
$sheet->setCellValue("B{$riga}", $totaleNoi);
$sheet->setCellValue("C{$riga}", $totaleLoro);
$sheet->setCellValue("D{$riga}", $totaleNoi - $totaleLoro);
$sheet->getStyle("A{$riga}:D{$riga}")->getFont()->setBold(true);
$sheet->getStyle("A{$riga}:D{$riga}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('ECF0F1');

$sheet->getStyle('A4:D' . $riga)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('B5:D' . $riga)->getNumberFormat()->setFormatCode('#,##0');
$sheet->getStyle('B5:D' . $riga)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

$noteRiga = $riga + 3;
$sheet->setCellValue("A{$noteRiga}", 'NOTE OPERATIVE');
$sheet->getStyle("A{$noteRiga}")->getFont()->setBold(true)->setSize(12);

$note = [
    'Differenze osservate aprile 2026:',
    '',
    '• B/N A4: +1 (rounding accettabile)',
    '• Colore A4: -2.332 click (MES sotto-conta vs Canon)',
    '• B/N A3:  +1.849 click (MES sopra-conta vs Canon)',
    '• Colore A3: +3.137 click (MES sopra-conta vs Canon)',
    '• Banner: identico ✓',
    '',
    'Causa probabile #1: BUG cron snapshot SNMP',
    '   Snapshot configurato 23:55 dal 10/04 al 04/05 ma stampante spenta dopo 17:00',
    '   = Nessuno snapshot tra 30/03 e 04/05 (35 giorni)',
    '   Fix applicato 04/05: snapshot 16:55 weekdays (prima spegnimento)',
    '',
    'Causa probabile #2: Mismatch classification SRA3 borderline',
    '   Soglia "piccolo/grande" SNMP Canon ≠ contratto fattura Canon',
    '   Formati 320x450, 329x480, 330x480 ambigui (SRA3 borderline)',
    '',
    'Riferimento Canon contratto SAE: e-Maintenance.canon.it (verifica con referente)',
];

foreach ($note as $i => $n) {
    $sheet->setCellValue('A' . ($noteRiga + 1 + $i), $n);
    $sheet->mergeCells('A' . ($noteRiga + 1 + $i) . ':D' . ($noteRiga + 1 + $i));
}

$writer = new Xlsx($spreadsheet);
$path = __DIR__ . '/contatori_canon_aprile_2026.xlsx';
$writer->save($path);

echo "Excel generato: {$path}\n";
echo "Totale MES: " . number_format($totaleNoi, 0, ',', '.') . "\n";
echo "Totale Canon: " . number_format($totaleLoro, 0, ',', '.') . "\n";
echo "Differenza: " . number_format($totaleNoi - $totaleLoro, 0, ',', '.') . "\n";
