<?php
/**
 * Confronta gli stati nel file Excel con quelli nel DB per verificare il sync.
 * Uso: php check_excel_sync.php [commessa]
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use App\Models\Ordine;
use PhpOffice\PhpSpreadsheet\IOFactory;

$filtroCommessa = $argv[1] ?? null;

// 1. Info sync
$excelDir = env('EXCEL_SYNC_PATH') ?: storage_path('app/excel_sync');
$excelPath = $excelDir . DIRECTORY_SEPARATOR . 'dashboard_mes.xlsx';
$tsPath = $excelDir . DIRECTORY_SEPARATOR . '.last_sync_timestamp';

echo "=== Stato Sync Excel ↔ DB ===\n\n";
echo "EXCEL_SYNC_ENABLED: " . (env('EXCEL_SYNC_ENABLED', false) ? 'SI' : 'NO') . "\n";
echo "Percorso Excel: $excelPath\n";

if (file_exists($excelPath)) {
    $fileMtime = filemtime($excelPath);
    echo "Ultima modifica file: " . date('d/m/Y H:i:s', $fileMtime) . "\n";
} else {
    echo "File Excel NON trovato!\n";
    exit(1);
}

if (file_exists($tsPath)) {
    $lastSync = (int) file_get_contents($tsPath);
    echo "Ultimo sync:         " . date('d/m/Y H:i:s', $lastSync) . "\n";

    if ($fileMtime > $lastSync) {
        echo "\n⚠ Il file Excel è stato modificato DOPO l'ultimo sync!\n";
        echo "  → Le modifiche NON sono ancora nel DB.\n";
        echo "  → Il prossimo sync le importerà (ogni 2 min).\n";
    } else {
        echo "\n✓ Il file Excel è già stato importato (sync OK).\n";
    }
} else {
    echo "Nessun timestamp di sync trovato.\n";
}

// 2. Confronto Excel vs DB
echo "\n=== Confronto Stati Excel vs DB ===\n\n";

try {
    $spreadsheet = IOFactory::load($excelPath);
} catch (\Exception $e) {
    echo "Impossibile leggere Excel: " . $e->getMessage() . "\n";
    exit(1);
}

$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray(null, false, false, true);

$differenze = [];
$isFirst = true;

foreach ($rows as $row) {
    if ($isFirst) { $isFirst = false; continue; }

    $id = $row['A'] ?? null;
    if (!$id || !is_numeric($id)) continue;

    $commessa = trim($row['B'] ?? '');
    if ($filtroCommessa && $commessa !== $filtroCommessa) continue;

    $excelStato = trim($row['C'] ?? '');
    $excelFase = trim($row['S'] ?? '');
    $excelQtaProd = trim($row['V'] ?? '');

    $fase = OrdineFase::with('ordine')->find((int) $id);
    if (!$fase) {
        $differenze[] = [
            'id' => $id,
            'commessa' => $commessa,
            'fase' => $excelFase,
            'diff' => "Fase ID $id non trovata nel DB!",
        ];
        continue;
    }

    $dbStato = (string) $fase->stato;
    $dbQtaProd = (string) ($fase->qta_prod ?? 0);

    $diffs = [];
    if ($excelStato !== '' && $excelStato !== $dbStato) {
        $diffs[] = "stato: Excel=$excelStato DB=$dbStato";
    }
    if ($excelQtaProd !== '' && abs((float)$excelQtaProd - (float)$dbQtaProd) > 0.001) {
        $diffs[] = "qta_prod: Excel=$excelQtaProd DB=$dbQtaProd";
    }

    if (!empty($diffs)) {
        $differenze[] = [
            'id' => $id,
            'commessa' => $fase->ordine->commessa ?? $commessa,
            'fase' => $excelFase,
            'diff' => implode(' | ', $diffs),
        ];
    }
}

if (empty($differenze)) {
    echo "✓ Nessuna differenza trovata" . ($filtroCommessa ? " per $filtroCommessa" : "") . " - DB e Excel sono allineati.\n";
} else {
    echo sprintf("%-8s %-16s %-25s %s\n", 'ID', 'Commessa', 'Fase', 'Differenze');
    echo str_repeat('-', 90) . "\n";
    foreach ($differenze as $d) {
        echo sprintf("%-8s %-16s %-25s %s\n", $d['id'], $d['commessa'], $d['fase'], $d['diff']);
    }
    echo "\nTrovate " . count($differenze) . " differenze.\n";
}
