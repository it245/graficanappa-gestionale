<?php
// Uso: php confronta_ean.php "C:\Users\Giovanni\Downloads\gestione codici a barre italiana confetti.xlsx"
// Confronta EAN dal file Excel con quelli nel database MES (tabella ean_prodotti)

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$file = $argv[1] ?? 'C:\Users\Giovanni\Downloads\gestione codici a barre italiana confetti.xlsx';

if (!file_exists($file)) {
    echo "File non trovato: $file\n";
    exit(1);
}

echo "========================================\n";
echo "  CONFRONTO EAN: Excel vs MES\n";
echo "========================================\n\n";

// 1. Leggi Excel
$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = @$reader->load($file);
$sheet = $spreadsheet->getActiveSheet();
$totRighe = $sheet->getHighestRow();

$excelEan = []; // codice_ean => articolo
for ($row = 2; $row <= $totRighe; $row++) {
    $articolo = trim((string)$sheet->getCell('B' . $row)->getValue());
    $codice = trim((string)$sheet->getCell('C' . $row)->getValue());
    if (empty($codice) || empty($articolo)) continue;
    $excelEan[$codice] = $articolo;
}
echo "Excel: " . count($excelEan) . " EAN trovati (su $totRighe righe)\n";

// 2. Leggi DB
$dbRows = DB::table('ean_prodotti')->get();
$dbEan = []; // codice_ean => articolo
foreach ($dbRows as $r) {
    $dbEan[$r->codice_ean] = $r->articolo;
}
echo "MES DB: " . count($dbEan) . " EAN presenti\n\n";

// 3. Confronto
$soloExcel = array_diff_key($excelEan, $dbEan);
$soloDb = array_diff_key($dbEan, $excelEan);
$comuni = array_intersect_key($excelEan, $dbEan);

// Tra i comuni, verifica articoli diversi
$articoloDiverso = [];
foreach ($comuni as $ean => $articoloExcel) {
    $articoloDb = $dbEan[$ean];
    if (strtolower(trim($articoloExcel)) !== strtolower(trim($articoloDb))) {
        $articoloDiverso[$ean] = ['excel' => $articoloExcel, 'db' => $articoloDb];
    }
}

echo "--- RIEPILOGO ---\n";
echo "  EAN in comune:              " . count($comuni) . "\n";
echo "  EAN solo nell'Excel:        " . count($soloExcel) . " (mancano nel MES)\n";
echo "  EAN solo nel MES:           " . count($soloDb) . " (non presenti nell'Excel)\n";
echo "  Articolo diverso (stesso EAN): " . count($articoloDiverso) . "\n\n";

// Dettaglio: solo nell'Excel (primi 30)
if (!empty($soloExcel)) {
    echo "--- SOLO NELL'EXCEL (primi 30) ---\n";
    $i = 0;
    foreach ($soloExcel as $ean => $articolo) {
        if (++$i > 30) { echo "  ... e altri " . (count($soloExcel) - 30) . "\n"; break; }
        echo "  $ean  →  $articolo\n";
    }
    echo "\n";
}

// Dettaglio: solo nel MES (primi 30)
if (!empty($soloDb)) {
    echo "--- SOLO NEL MES (primi 30) ---\n";
    $i = 0;
    foreach ($soloDb as $ean => $articolo) {
        if (++$i > 30) { echo "  ... e altri " . (count($soloDb) - 30) . "\n"; break; }
        echo "  $ean  →  $articolo\n";
    }
    echo "\n";
}

// Dettaglio: articolo diverso
if (!empty($articoloDiverso)) {
    echo "--- ARTICOLO DIVERSO (stesso EAN) ---\n";
    foreach ($articoloDiverso as $ean => $info) {
        echo "  $ean\n";
        echo "    Excel: {$info['excel']}\n";
        echo "    MES:   {$info['db']}\n";
    }
    echo "\n";
}

echo "========================================\n";
echo "  FINE CONFRONTO\n";
echo "========================================\n";
