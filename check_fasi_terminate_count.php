<?php
/**
 * Confronta fasi terminate nel DB vs nell'Excel.
 * Eseguire sul server: php check_fasi_terminate_count.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
error_reporting(E_ALL & ~E_DEPRECATED);

use App\Models\OrdineFase;

// Conta fasi nel DB per stato
$stati = OrdineFase::selectRaw('stato, COUNT(*) as cnt')
    ->groupBy('stato')
    ->orderBy('stato')
    ->pluck('cnt', 'stato');

echo "=== FASI NEL DATABASE ===\n";
$totale = 0;
foreach ($stati as $stato => $cnt) {
    $label = match((string) $stato) {
        '0' => 'non iniziata',
        '1' => 'pronta',
        '2' => 'avviata',
        '3' => 'terminata',
        '4' => 'consegnata',
        '5' => 'esterno',
        default => $stato,
    };
    echo "  Stato $stato ($label): $cnt\n";
    $totale += $cnt;
}
echo "  TOTALE: $totale\n";

// Fasi terminate (stato 3)
$terminate = OrdineFase::where('stato', 3)->count();
echo "\n=== CONFRONTO ===\n";
echo "  Fasi stato 3 nel DB: $terminate\n";

// Conta righe nel foglio Terminate dell'Excel
$excelPath = env('EXCEL_SYNC_PATH', storage_path('app/excel_sync'));
$file = rtrim($excelPath, '/\\') . DIRECTORY_SEPARATOR . 'dashboard_mes.xlsx';

if (file_exists($file)) {
    $mod = date('d/m/Y H:i:s', filemtime($file));
    echo "  File Excel: $file\n";
    echo "  Ultima modifica: $mod\n";

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $sheetNames = $spreadsheet->getSheetNames();
        echo "  Fogli: " . implode(', ', $sheetNames) . "\n";

        // Cerca foglio Terminate
        foreach ($sheetNames as $name) {
            $sheet = $spreadsheet->getSheetByName($name);
            $rows = $sheet->getHighestRow();
            echo "  Foglio '$name': $rows righe\n";
        }
    } catch (\Exception $e) {
        echo "  Errore lettura Excel: " . $e->getMessage() . "\n";
    }
} else {
    echo "  File Excel NON TROVATO: $file\n";
}

echo "\nCompletato.\n";
