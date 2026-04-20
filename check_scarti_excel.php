<?php
/**
 * Legge dashboard_mes.xlsx e mostra Scarti Prev. (AK) per commesse stampa.
 * Confronta con fogli_scarto DB per identificare disallineamenti.
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

error_reporting(E_ALL & ~E_DEPRECATED);

$path = $argv[1] ?? env('EXCEL_SYNC_PATH');
if (!$path) $path = storage_path('app/excel_sync');
if (is_dir($path)) $path = rtrim($path, '/\\') . '/dashboard_mes.xlsx';

if (!file_exists($path)) die("File non trovato: $path\n");

echo "=== Excel: $path ===\n\n";

// Leggi Excel foglio "tutto" (prima sheet)
$ss = IOFactory::load($path);
$sheet = $ss->getSheetByName('tutto') ?? $ss->getSheetByName('Terminate') ?? $ss->getActiveSheet();
echo "Foglio: " . $sheet->getTitle() . "\n\n";

$rows = $sheet->toArray(null, false, false, true);

// Commesse target (da Prinect riepilogo)
$target = [
    '0066830-26', '0067106-26', '0067061-26', '0066944-26',
    '0067019-26', '0067100-26', '0067055-26',
];

echo sprintf("%-14s %-20s %10s %15s %15s\n", "Commessa", "Fase", "Scarti(AJ)", "ScartiPrev(AK)", "DB fogli_scarto");
echo str_repeat('-', 80) . "\n";

$first = true;
foreach ($rows as $r) {
    if ($first) { $first = false; continue; }
    $comm = trim((string)($r['B'] ?? ''));
    $fase = trim((string)($r['S'] ?? ''));
    if (!in_array($comm, $target)) continue;
    if (!str_starts_with(strtoupper($fase), 'STAMPA')) continue;

    $scarti = $r['AJ'] ?? '';
    $scartiPrev = $r['AK'] ?? '';

    // Check DB fogli_scarto
    $faseId = $r['A'] ?? null;
    $dbFogliScarto = null;
    if (is_numeric($faseId)) {
        $dbFogliScarto = DB::table('ordine_fasi')->where('id', $faseId)->value('fogli_scarto');
    }

    printf("%-14s %-20s %10s %15s %15s\n",
        $comm, substr($fase, 0, 20), $scarti ?: '-',
        $scartiPrev ?: '-', $dbFogliScarto ?? 'NULL');
}
