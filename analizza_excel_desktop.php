<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;

$paths = [
    'C:\Users\Giovanni\Desktop\dashboard_mes.xlsx',
    'C:\Users\Giovanni\Desktop\dashboard_mes_presenze.xlsx',
];

foreach ($paths as $path) {
    if (!file_exists($path)) {
        echo "MANCA: $path\n\n";
        continue;
    }
    echo "\n======================================================\n";
    echo "FILE: $path\n";
    echo "Modified: " . date('Y-m-d H:i:s', filemtime($path)) . "\n";
    echo "Size: " . round(filesize($path)/1024, 1) . " KB\n";
    echo "======================================================\n";

    try {
        $sp = IOFactory::load($path);
        foreach ($sp->getAllSheets() as $sheet) {
            $title = $sheet->getTitle();
            $rows = $sheet->toArray();
            $nRows = count($rows);
            $header = $rows[0] ?? [];
            $nCols = count($header);

            echo "\n--- Sheet: '$title' ($nRows righe, $nCols colonne) ---\n";
            echo "Header:\n";
            foreach ($header as $i => $h) {
                $col = chr(65 + $i);
                if ($i >= 26) $col = chr(64 + intdiv($i, 26)) . chr(65 + ($i % 26));
                echo "  [$col] $h\n";
            }

            // Cerca colonne inchiostro / ink
            $inkCols = [];
            foreach ($header as $i => $h) {
                if (preg_match('/inchiostr|ink|cmyk/i', (string)$h)) {
                    $inkCols[$i] = $h;
                }
            }
            if ($inkCols) {
                echo "\n!!! Colonne ink/inchiostro trovate: " . json_encode($inkCols, JSON_UNESCAPED_UNICODE) . "\n";
                // Conta righe con valore popolato
                foreach ($inkCols as $i => $name) {
                    $popolate = 0;
                    $vuote = 0;
                    foreach (array_slice($rows, 1) as $r) {
                        $v = $r[$i] ?? null;
                        if ($v !== null && $v !== '') $popolate++;
                        else $vuote++;
                    }
                    echo "  '$name': $popolate popolate, $vuote vuote\n";
                }
            } else {
                echo "\nNessuna colonna inchiostro/ink in header.\n";
            }

            // Stato campioni: prime 3 righe valori
            if ($nRows > 1) {
                echo "\nPrime 2 righe campione:\n";
                for ($r = 1; $r <= min(2, $nRows - 1); $r++) {
                    $row = $rows[$r];
                    echo "  Row $r: ";
                    $parts = [];
                    foreach (array_slice($row, 0, 8) as $j => $v) {
                        $parts[] = trim((string)$v);
                    }
                    echo implode(' | ', $parts) . "\n";
                }
            }
        }
    } catch (\Exception $e) {
        echo "ERRORE: " . $e->getMessage() . "\n";
    }
}
