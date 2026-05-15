<?php
/**
 * Elenca le tabelle "Altri" (prefisso non standard) di Onda con conteggio righe.
 * Filtra per identificare temp/sys/legacy vs tabelle attive.
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$prefissiNoti = ['ATT','PAS','PRD','MAG','COG','STD','OC','TEL','ANA','CSP','CRM'];

$tabelle = DB::connection('onda')->select("
    SELECT
        t.TABLE_NAME AS nome,
        COALESCE(SUM(p.rows), 0) AS righe
    FROM INFORMATION_SCHEMA.TABLES t
    LEFT JOIN sys.tables st ON st.name = t.TABLE_NAME
    LEFT JOIN sys.partitions p ON p.object_id = st.object_id AND p.index_id IN (0,1)
    WHERE t.TABLE_TYPE = 'BASE TABLE'
    GROUP BY t.TABLE_NAME
    ORDER BY righe DESC
");

$altri = [];
foreach ($tabelle as $t) {
    $isAltro = true;
    foreach ($prefissiNoti as $p) {
        if (str_starts_with($t->nome, $p)) { $isAltro = false; break; }
    }
    if ($isAltro) $altri[] = $t;
}

echo "Tabelle 'Altri': " . count($altri) . "\n";
echo "Vive (>0 righe): " . count(array_filter($altri, fn($t) => $t->righe > 0)) . "\n";
echo "Vuote: " . count(array_filter($altri, fn($t) => $t->righe == 0)) . "\n\n";

echo "=== Top 50 per righe ===\n";
$i = 0;
foreach ($altri as $t) {
    if ($i++ >= 50) break;
    if ($t->righe == 0) break;
    echo "  " . str_pad((string)number_format((int)$t->righe, 0, ',', '.'), 12, ' ', STR_PAD_LEFT) . " | {$t->nome}\n";
}

echo "\n=== Raggruppamento per pattern nome (primi 6 char) ===\n";
$gruppi = [];
foreach ($altri as $t) {
    $k = substr($t->nome, 0, 6);
    $gruppi[$k] = ($gruppi[$k] ?? 0) + 1;
}
arsort($gruppi);
foreach (array_slice($gruppi, 0, 20, true) as $k => $n) {
    echo "  $k -> $n tabelle\n";
}

// Salva lista completa in file
$out = "# Tabelle Altri Onda (" . count($altri) . " totali)\n\n";
foreach ($altri as $t) {
    $righe = number_format((int)$t->righe, 0, ',', '.');
    $out .= "- `{$t->nome}` — $righe righe\n";
}
$file = __DIR__ . '/docs/ALTRI_ONDA.md';
file_put_contents($file, $out);
echo "\nFile salvato: $file\n";
