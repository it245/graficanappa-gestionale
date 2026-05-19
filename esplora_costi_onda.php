<?php
/**
 * Esplora tabelle Onda relate a costi/preventivi/cicli per integrazione MES analisi costi.
 * Output: nome tabella + righe stimate + colonne principali.
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$patterns = [
    'STDArticoli', 'STDArticoliDati%', 'STDArti%',
    '%Prezzo%', '%Prz%',
    '%Cicl%', '%Rapport%', '%Consunt%', '%Preventiv%', '%Offerta%',
    '%Commess%', '%Fase%', '%Ciclo%', '%Lavoraz%',
    'ATTDoc%', 'ATT%Cost%',
    'NVP%',
];

echo "=== TABELLE ONDA candidato costi/preventivi/cicli ===\n";
$seen = [];
foreach ($patterns as $p) {
    $tabs = DB::connection('onda')->select(
        "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE ? ORDER BY TABLE_NAME",
        [$p]
    );
    foreach ($tabs as $t) {
        $name = $t->TABLE_NAME;
        if (isset($seen[$name])) continue;
        $seen[$name] = true;

        try {
            $count = DB::connection('onda')->selectOne("SELECT COUNT(*) AS c FROM [$name]")->c;
        } catch (\Throwable $e) {
            $count = 'N/A';
        }
        if ($count === 0 || $count === '0') continue; // skip tabelle vuote

        // Solo se contiene colonna costo/prezzo/preventivo/cic/tempo/ore/euro
        $cols = DB::connection('onda')->select(
            "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? ORDER BY ORDINAL_POSITION",
            [$name]
        );
        $relevante = false;
        foreach ($cols as $c) {
            if (preg_match('/cost|prezz|prz|preventiv|offerta|consunt|cicl|tempo|ore|euro|valor|import/i', $c->COLUMN_NAME)) {
                $relevante = true;
                break;
            }
        }
        if (!$relevante) continue;

        echo "\n→ {$name} (righe: {$count})\n";
        foreach ($cols as $c) {
            if (preg_match('/cost|prezz|prz|preventiv|offerta|consunt|cicl|tempo|ore|euro|valor|import|cod|qta/i', $c->COLUMN_NAME)) {
                echo "    {$c->COLUMN_NAME} ({$c->DATA_TYPE})\n";
            }
        }
    }
}
