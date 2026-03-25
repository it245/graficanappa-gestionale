<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$excelPath = env('EXCEL_SYNC_PATH', storage_path('app/excel_sync')) . '/dashboard_mes.xlsx';

echo "=== CONFRONTO FASI STATO 3: EXCEL vs MES ===" . PHP_EOL;
echo "Excel: {$excelPath}" . PHP_EOL;
echo "Data: " . date('d/m/Y H:i') . PHP_EOL . PHP_EOL;

if (!file_exists($excelPath)) {
    echo "File Excel non trovato!" . PHP_EOL;
    exit(1);
}

// Leggi Excel foglio "Terminate"
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
$spreadsheet = $reader->load($excelPath);

// Cerca foglio Terminate
$sheet = $spreadsheet->getSheetByName('Terminate');
if (!$sheet) {
    echo "Foglio 'Terminate' non trovato nell'Excel." . PHP_EOL;
    // Prova il primo foglio
    $sheet = $spreadsheet->getSheet(0);
    echo "Uso foglio: " . $sheet->getTitle() . PHP_EOL;
}

$excelFasi = [];
$lastRow = $sheet->getHighestRow();
echo "Righe Excel: {$lastRow}" . PHP_EOL;

for ($row = 2; $row <= $lastRow; $row++) {
    $id = $sheet->getCell('A' . $row)->getValue();
    $commessa = trim($sheet->getCell('B' . $row)->getValue() ?? '');
    $stato = $sheet->getCell('C' . $row)->getValue();
    $fase = trim($sheet->getCell('S' . $row)->getValue() ?? '');

    if (!$id || !$commessa) continue;

    $excelFasi[$id] = [
        'id' => $id,
        'commessa' => $commessa,
        'stato' => $stato,
        'fase' => $fase,
    ];
}

echo "Fasi nel foglio Excel: " . count($excelFasi) . PHP_EOL;

// Fasi stato 3 nel MES
$mesFasi = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->where('ordine_fasi.stato', 3)
    ->whereNull('ordine_fasi.deleted_at')
    ->select('ordine_fasi.id', 'ordini.commessa', 'ordine_fasi.stato', 'ordine_fasi.fase')
    ->get()
    ->keyBy('id');

echo "Fasi stato 3 nel MES: " . $mesFasi->count() . PHP_EOL . PHP_EOL;

// Confronto
$soloExcel = 0;
$soloMes = 0;
$corrispondenti = 0;
$statoDiv = 0;

// Fasi in Excel ma non nel MES (o con stato diverso)
foreach ($excelFasi as $id => $ef) {
    if (isset($mesFasi[$id])) {
        $corrispondenti++;
    } else {
        $soloExcel++;
        if ($soloExcel <= 10) {
            echo "SOLO EXCEL: ID:{$id} | {$ef['commessa']} | {$ef['fase']} | stato:{$ef['stato']}" . PHP_EOL;
        }
    }
}

// Fasi nel MES ma non in Excel
foreach ($mesFasi as $id => $mf) {
    if (!isset($excelFasi[$id])) {
        $soloMes++;
        if ($soloMes <= 10) {
            echo "SOLO MES: ID:{$id} | {$mf->commessa} | {$mf->fase}" . PHP_EOL;
        }
    }
}

if ($soloExcel > 10) echo "... e altre " . ($soloExcel - 10) . " solo in Excel" . PHP_EOL;
if ($soloMes > 10) echo "... e altre " . ($soloMes - 10) . " solo nel MES" . PHP_EOL;

echo PHP_EOL . "=== RIEPILOGO ===" . PHP_EOL;
echo "Corrispondenti: {$corrispondenti}" . PHP_EOL;
echo "Solo in Excel: {$soloExcel}" . PHP_EOL;
echo "Solo nel MES: {$soloMes}" . PHP_EOL;

if ($soloExcel === 0 && $soloMes === 0) {
    echo PHP_EOL . "TUTTO ALLINEATO" . PHP_EOL;
}

echo "DONE" . PHP_EOL;
