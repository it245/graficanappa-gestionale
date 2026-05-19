<?php
/**
 * Esplora tabelle Onda relate a costi/preventivi/cicli per integrazione MES analisi costi.
 * Output: nome tabella + righe stimate + colonne principali.
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$patterns = ['ATTPreventiv%', 'ATTArtiCicli%', 'ATTRapport%', 'ATTConsuntiv%', 'STDArticoli', 'STDListini%', 'ATTCommesse%', '%Costo%', '%Cost'];

echo "=== TABELLE ONDA candidato costi/preventivi ===\n";
foreach ($patterns as $p) {
    $tabs = DB::connection('onda')->select(
        "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE ? ORDER BY TABLE_NAME",
        [$p]
    );
    foreach ($tabs as $t) {
        $name = $t->TABLE_NAME;
        try {
            $count = DB::connection('onda')->selectOne("SELECT COUNT(*) AS c FROM [$name]")->c;
        } catch (\Throwable $e) {
            $count = 'N/A';
        }
        echo "\n→ {$name} (righe: {$count})\n";

        $cols = DB::connection('onda')->select(
            "SELECT TOP 15 COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? ORDER BY ORDINAL_POSITION",
            [$name]
        );
        foreach ($cols as $c) {
            echo "    {$c->COLUMN_NAME} ({$c->DATA_TYPE})\n";
        }
    }
}
