<?php
/**
 * Verifica allineamento DB ↔ Excel per qta_prod su STAMPA offset.
 * Mostra discrepanze se Excel ha valori diversi dal DB.
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

// 1. Leggi DB (fasi STAMPA recenti)
$fasi = DB::table('ordine_fasi as orf')
    ->join('ordini as o', 'o.id', '=', 'orf.ordine_id')
    ->where(function($q){
        $q->where('orf.fase', 'LIKE', 'STAMPAXL%')
          ->orWhere('orf.fase', 'LIKE', 'STAMPA XL%')
          ->orWhere('orf.fase', 'STAMPA');
    })
    ->where('orf.qta_prod', '>', 0)
    ->where('o.created_at', '>=', now()->subDays(60))
    ->select('orf.id', 'o.commessa', 'orf.fase', 'orf.qta_prod as db_qta', 'orf.fogli_buoni as db_buoni', 'orf.fogli_scarto as db_scarto')
    ->get()
    ->keyBy('id');

echo "Fasi STAMPA in DB ultimi 60gg: " . count($fasi) . "\n\n";

// 2. Leggi Excel (usa env path)
$dir = env('EXCEL_SYNC_PATH') ?: storage_path('app/excel_sync');
$path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'dashboard_mes.xlsx';
if (!file_exists($path)) {
    echo "Excel non trovato: $path\n";
    exit;
}
echo "Excel path: $path\n";
echo "Excel modified: " . date('Y-m-d H:i:s', filemtime($path)) . "\n\n";

error_reporting(E_ALL & ~E_DEPRECATED);
$spreadsheet = IOFactory::load($path);

$excelById = [];
foreach ($spreadsheet->getSheetNames() as $sheetName) {
    $sheet = $spreadsheet->getSheetByName($sheetName);
    $rows = $sheet->toArray(null, false, false, true);
    $first = true;
    foreach ($rows as $r) {
        if ($first) { $first = false; continue; }
        $id = $r['A'] ?? null;
        if (!$id || !is_numeric($id)) continue;
        $excelById[(int)$id] = [
            'fase'    => $r['S'] ?? '',
            'qta'     => $r['V'] ?? null,
            'sheet'   => $sheetName,
        ];
    }
}

echo "Righe Excel: " . count($excelById) . "\n\n";

// 3. Confronta
$ok = 0; $diff = 0; $mancanti = 0;
echo "=== DISCREPANZE ===\n";
foreach ($fasi as $id => $f) {
    if (!isset($excelById[$id])) {
        $mancanti++;
        echo "  fase $id ({$f->commessa} {$f->fase}): DB={$f->db_qta} EXCEL=MISSING\n";
        continue;
    }
    $excelQta = (float) ($excelById[$id]['qta'] ?? 0);
    $dbQta = (float) $f->db_qta;
    if (abs($excelQta - $dbQta) < 0.5) {
        $ok++;
    } else {
        $diff++;
        echo "  fase $id ({$f->commessa}): DB=$dbQta vs Excel=$excelQta (diff " . ($excelQta - $dbQta) . ")\n";
    }
}

echo "\n=== RIEPILOGO ===\n";
echo "Allineate: $ok\n";
echo "Differenti: $diff\n";
echo "Mancanti in Excel: $mancanti\n";
