<?php
/**
 * Esporta tutte le fustelle (codici FS) dalle commesse nel MES
 * con cliente, descrizione e commessa per creare l'anagrafica cliché.
 *
 * Uso: php export_fustelle_clice.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ordine;
use App\Helpers\DescrizioneParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

echo "Esportando fustelle dalle commesse...\n";

// Prendi tutte le commesse con fasi stampa a caldo
$ordini = Ordine::whereHas('fasi', function($q) {
    $q->where('fase', 'LIKE', 'STAMPACALDOJOH%');
})->orderBy('cliente_nome')->orderBy('commessa')->get();

$fustelleMap = []; // [codice_fs => [clienti, descrizioni, commesse]]

foreach ($ordini as $o) {
    $desc = $o->descrizione ?? '';
    $cliente = $o->cliente_nome ?? '';
    $notePre = $o->note_prestampa ?? '';

    $fsCodice = DescrizioneParser::parseFustella($desc, $cliente, $notePre);
    if (!$fsCodice) continue;

    // Può contenere "FS0001 / FS0002"
    $codici = array_map('trim', explode('/', $fsCodice));
    foreach ($codici as $codice) {
        if (!$codice) continue;
        if (!isset($fustelleMap[$codice])) {
            $fustelleMap[$codice] = [
                'codice_fs' => $codice,
                'clienti' => [],
                'commesse' => [],
            ];
        }
        $fustelleMap[$codice]['clienti'][$cliente] = $cliente;
        $fustelleMap[$codice]['commesse'][] = [
            'commessa' => $o->commessa,
            'cliente' => $cliente,
            'descrizione' => $desc,
        ];
    }
}

ksort($fustelleMap);

// === EXCEL ===
$spreadsheet = new Spreadsheet();

// FOGLIO 1: Riepilogo fustelle
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Fustelle - Riepilogo');

$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D11317']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];

$sheet->setCellValue('A1', 'Codice FS');
$sheet->setCellValue('B1', 'Cliente');
$sheet->setCellValue('C1', 'N. Commesse');
$sheet->setCellValue('D1', 'N. Cliché (stima min 4)');
$sheet->setCellValue('E1', 'Posizione Scaffale');
$sheet->setCellValue('F1', 'Note');
$sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

$row = 2;
$totalClice = 0;
foreach ($fustelleMap as $fs) {
    $clientiStr = implode(', ', $fs['clienti']);
    $numComm = count($fs['commesse']);
    $stimaClice = max(4, $numComm); // minimo 4 cliché per FS

    $sheet->setCellValue("A$row", $fs['codice_fs']);
    $sheet->setCellValue("B$row", $clientiStr);
    $sheet->setCellValue("C$row", $numComm);
    $sheet->setCellValue("D$row", $stimaClice);
    $sheet->setCellValue("E$row", ''); // da compilare
    $sheet->setCellValue("F$row", ''); // da compilare

    $totalClice += $stimaClice;
    $row++;
}

// Riga totale
$sheet->setCellValue("A$row", 'TOTALE');
$sheet->setCellValue("C$row", array_sum(array_map(fn($fs) => count($fs['commesse']), $fustelleMap)));
$sheet->setCellValue("D$row", $totalClice);
$sheet->getStyle("A$row:F$row")->getFont()->setBold(true);
$sheet->getStyle("A2:F$row")->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
foreach (range('A', 'F') as $c) $sheet->getColumnDimension($c)->setAutoSize(true);

// FOGLIO 2: Dettaglio commesse per fustella
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Dettaglio Commesse');

$sheet2->setCellValue('A1', 'Codice FS');
$sheet2->setCellValue('B1', 'Commessa');
$sheet2->setCellValue('C1', 'Cliente');
$sheet2->setCellValue('D1', 'Descrizione');
$sheet2->setCellValue('E1', 'Variante/Cliché');
$sheet2->setCellValue('F1', 'N. Cliché (pennarello)');
$sheet2->getStyle('A1:F1')->applyFromArray($headerStyle);

$row = 2;
foreach ($fustelleMap as $fs) {
    foreach ($fs['commesse'] as $c) {
        $sheet2->setCellValue("A$row", $fs['codice_fs']);
        $sheet2->setCellValue("B$row", $c['commessa']);
        $sheet2->setCellValue("C$row", $c['cliente']);
        $sheet2->setCellValue("D$row", substr($c['descrizione'], 0, 80));
        $sheet2->setCellValue("E$row", ''); // da compilare: quale variante/lettera
        $sheet2->setCellValue("F$row", ''); // da compilare: C-001, C-002...
        $row++;
    }
}
$sheet2->getStyle("A2:F$row")->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
foreach (range('A', 'F') as $c) $sheet2->getColumnDimension($c)->setAutoSize(true);

// FOGLIO 3: Anagrafica cliché (da compilare)
$sheet3 = $spreadsheet->createSheet();
$sheet3->setTitle('Anagrafica Cliché');

$sheet3->setCellValue('A1', 'N. Cliché');
$sheet3->setCellValue('B1', 'Codice FS');
$sheet3->setCellValue('C1', 'Variante');
$sheet3->setCellValue('D1', 'Cliente');
$sheet3->setCellValue('E1', 'Descrizione');
$sheet3->setCellValue('F1', 'Posizione Scaffale');
$sheet3->setCellValue('G1', 'Stato');
$sheet3->getStyle('A1:G1')->applyFromArray($headerStyle);

// Pre-compila con numerazione C-001, C-002...
$row = 2;
$numClice = 1;
foreach ($fustelleMap as $fs) {
    $stimaClice = max(4, count($fs['commesse']));
    $clienteDefault = array_values($fs['clienti'])[0] ?? '-';
    for ($i = 0; $i < $stimaClice; $i++) {
        $sheet3->setCellValue("A$row", 'C-' . str_pad($numClice, 3, '0', STR_PAD_LEFT));
        $sheet3->setCellValue("B$row", $fs['codice_fs']);
        $sheet3->setCellValue("C$row", ''); // variante da compilare
        $sheet3->setCellValue("D$row", $clienteDefault);
        $sheet3->setCellValue("E$row", '');
        $sheet3->setCellValue("F$row", '');
        $sheet3->setCellValue("G$row", 'Disponibile');
        $numClice++;
        $row++;
    }
}
$sheet3->getStyle("A2:G$row")->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
foreach (range('A', 'G') as $c) $sheet3->getColumnDimension($c)->setAutoSize(true);

// Salva
$nomeFile = 'Anagrafica_Clice_Fustelle.xlsx';
$percorso = __DIR__ . '/storage/' . $nomeFile;
$writer = new Xlsx($spreadsheet);
$writer->save($percorso);

echo "\nFustelle trovate: " . count($fustelleMap) . "\n";
echo "Cliché stimati (min 4 per FS): $totalClice\n";
echo "File: $percorso\n";
