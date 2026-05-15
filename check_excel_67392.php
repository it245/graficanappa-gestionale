<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;

$paths = [
    'C:\Users\Giovanni\Desktop\dashboard_mes.xlsx',
    'C:\condivisa\mes\dashboard_mes.xlsx',
];

foreach ($paths as $path) {
    if (!file_exists($path)) {
        echo "MANCA: $path\n";
        continue;
    }
    echo "\n=== $path ===\n";
    echo "Modified: " . date('Y-m-d H:i:s', filemtime($path)) . "\n";
    echo "Size: " . round(filesize($path)/1024, 1) . " KB\n";

    try {
        $spreadsheet = IOFactory::load($path);
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $title = $sheet->getTitle();
            $rows = $sheet->toArray();
            $header = $rows[0] ?? [];
            $colCommessa = array_search('Commessa', $header);
            $colFase = array_search('Fase', $header);
            $colQta = array_search('Qta Prod', $header);
            $colQtaPrinect = array_search('Qta Prod. Prinect', $header);

            foreach ($rows as $i => $r) {
                if ($i === 0) continue;
                $com = $r[$colCommessa] ?? '';
                if (strpos((string)$com, '67392') !== false) {
                    $fase = $r[$colFase] ?? '';
                    $qta = $r[$colQta] ?? '';
                    $qtaP = $r[$colQtaPrinect] ?? '';
                    echo "  [$title row " . ($i+1) . "] commessa=$com fase=$fase qta_prod=$qta qta_prinect=$qtaP\n";
                }
            }
        }
    } catch (\Exception $e) {
        echo "ERRORE: " . $e->getMessage() . "\n";
    }
}
