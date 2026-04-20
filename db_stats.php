<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$dbName = config('database.connections.mysql.database');
$tables = DB::select("SELECT table_name AS nome,
                              table_rows AS righe,
                              ROUND(data_length/1024/1024, 1) AS mb_dati,
                              ROUND(index_length/1024/1024, 1) AS mb_indici
                       FROM information_schema.tables
                       WHERE table_schema = ?
                       ORDER BY table_rows DESC", [$dbName]);

echo "=== DB: {$dbName} ===\n\n";
echo sprintf("%-35s %12s %10s %10s\n", "Tabella", "Righe", "MB dati", "MB indici");
echo str_repeat('-', 70) . "\n";
$totRighe = 0; $totMb = 0;
foreach ($tables as $t) {
    printf("%-35s %12s %10s %10s\n",
        $t->nome,
        number_format($t->righe, 0, ',', '.'),
        $t->mb_dati ?? 0,
        $t->mb_indici ?? 0);
    $totRighe += $t->righe;
    $totMb += ($t->mb_dati ?? 0) + ($t->mb_indici ?? 0);
}
echo str_repeat('-', 70) . "\n";
echo sprintf("%-35s %12s %10s\n", "TOTALE", number_format($totRighe, 0, ',', '.'), round($totMb, 1) . ' MB');
