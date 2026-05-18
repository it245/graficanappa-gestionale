<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Ordine;

$file = 'C:\condivisa\mes\ORDINE ASTUCCI.xlsx';
echo "File: $file mod=" . date('Y-m-d H:i:s', filemtime($file)) . "\n\n";

$sp = IOFactory::load($file);
$sheet = $sp->getActiveSheet();

$mapDettaglio = [];
$mapCommessa = [];

foreach ($sheet->getRowIterator(2) as $row) {
    $r = $row->getRowIndex();
    $commessa = trim((string)$sheet->getCell("F$r")->getValue());
    $desc = trim((string)$sheet->getCell("B$r")->getValue());
    $rif = trim((string)$sheet->getCell("G$r")->getValue());
    if (!$rif) continue;

    $commesse = array_map('trim', explode('-', $commessa));
    $tutteNum = collect($commesse)->every(fn($c) => is_numeric($c));

    if ($tutteNum && count($commesse) > 1) {
        foreach ($commesse as $c) {
            if (!isset($mapCommessa[$c])) $mapCommessa[$c] = $rif;
        }
    } else {
        if (!isset($mapCommessa[$commessa])) $mapCommessa[$commessa] = $rif;
    }
}

echo "=== mapCommessa entries ===\n";
foreach ($mapCommessa as $k => $v) {
    echo "  '$k' => '$v'\n";
}

echo "\n=== Test specifico 67201 ===\n";
echo "  mapCommessa['67201'] = " . ($mapCommessa['67201'] ?? 'NON ESISTE') . "\n";
echo "  mapCommessa['67203'] = " . ($mapCommessa['67203'] ?? 'NON ESISTE') . "\n";

echo "\n=== Ordini MES 67201/67203 con commCorta ===\n";
$ordini = Ordine::where('commessa', 'LIKE', '%67201%')->orWhere('commessa', 'LIKE', '%67203%')->get(['id', 'commessa', 'ordine_cliente']);
foreach ($ordini as $o) {
    $commCorta = ltrim(explode('-', $o->commessa)[0] ?? '', '0') ?: '0';
    $found = $mapCommessa[$commCorta] ?? 'MISS';
    echo "  id={$o->id} commessa={$o->commessa} → commCorta='$commCorta' → map='$found' | attuale={$o->ordine_cliente}\n";
}
