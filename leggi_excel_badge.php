<?php
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'C:\Users\Giovanni\Downloads\anagrafica_badge_matricole.xlsx';
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();

echo str_pad('COGNOME', 25) . str_pad('NOME', 20) . str_pad('BADGE', 12) . str_pad('MATRICOLA', 12) . "NOTE\n";
echo str_repeat('-', 90) . "\n";

foreach ($sheet->getRowIterator(2) as $row) {
    $r = $row->getRowIndex();
    $cognome = trim($sheet->getCell("A$r")->getValue() ?? '');
    $nome = trim($sheet->getCell("B$r")->getValue() ?? '');
    $badge = trim($sheet->getCell("C$r")->getValue() ?? '');
    $matricola = trim($sheet->getCell("D$r")->getValue() ?? '');
    $note = trim($sheet->getCell("E$r")->getValue() ?? '');
    if (empty($cognome)) continue;
    echo str_pad($cognome, 25) . str_pad($nome, 20) . str_pad($badge, 12) . str_pad($matricola, 12) . "$note\n";
}
