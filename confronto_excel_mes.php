<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$excelPath = env('EXCEL_SYNC_PATH', storage_path('app/excel_sync')) . '/dashboard_mes.xlsx';

echo "=== CONFRONTO EXCEL vs MES ===" . PHP_EOL;
echo "Data: " . date('d/m/Y H:i:s') . PHP_EOL . PHP_EOL;

$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
$spreadsheet = $reader->load($excelPath);

// Controlla entrambi i fogli
foreach (['tutto', 'Terminate'] as $foglio) {
    $sheet = $spreadsheet->getSheetByName($foglio);
    if (!$sheet) { echo "Foglio '{$foglio}' non trovato" . PHP_EOL; continue; }

    $lastRow = $sheet->getHighestRow();
    echo "--- Foglio: {$foglio} ({$lastRow} righe) ---" . PHP_EOL;

    $discrepanze = 0;
    for ($row = 2; $row <= $lastRow; $row++) {
        $id = $sheet->getCell('A' . $row)->getValue();
        $commessa = trim($sheet->getCell('B' . $row)->getValue() ?? '');
        $statoExcel = $sheet->getCell('C' . $row)->getValue();
        $fase = trim($sheet->getCell('S' . $row)->getValue() ?? '');

        if (!$id) continue;

        // Cerca nel DB
        $dbFase = DB::table('ordine_fasi')->where('id', $id)->first();

        if (!$dbFase) {
            echo "  ID:{$id} | {$commessa} | {$fase} | Excel stato:{$statoExcel} → NON ESISTE nel DB" . PHP_EOL;
            $discrepanze++;
        } elseif ($dbFase->stato != $statoExcel) {
            echo "  ID:{$id} | {$commessa} | {$fase} | Excel stato:{$statoExcel} → DB stato:{$dbFase->stato}" . PHP_EOL;
            $discrepanze++;
        }
    }

    if ($discrepanze === 0) echo "  Tutto allineato" . PHP_EOL;
    else echo "  Discrepanze: {$discrepanze}" . PHP_EOL;
    echo PHP_EOL;
}

echo "DONE" . PHP_EOL;
