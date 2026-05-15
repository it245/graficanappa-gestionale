<?php
/**
 * Esplora schema COGScadenze + sample data per cashflow forecast dashboard.
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Schema COGScadenze ===\n";
$cols = DB::connection('onda')->select("
    SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'COGScadenze'
    ORDER BY ORDINAL_POSITION
");
foreach ($cols as $c) {
    $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
    echo "  {$c->COLUMN_NAME} - {$c->DATA_TYPE}{$len} ".($c->IS_NULLABLE === 'YES' ? '?' : '')."\n";
}

echo "\n=== Top 3 record campione ===\n";
$rows = DB::connection('onda')->select("SELECT TOP 3 * FROM COGScadenze ORDER BY DataScadenza DESC");
foreach ($rows as $r) {
    print_r((array)$r);
    echo "---\n";
}

echo "\n=== Conteggi per stato ===\n";
$counts = DB::connection('onda')->select("
    SELECT
        COUNT(*) AS totale,
        SUM(CASE WHEN DataScadenza > GETDATE() THEN 1 ELSE 0 END) AS future,
        SUM(CASE WHEN DataScadenza <= GETDATE() THEN 1 ELSE 0 END) AS passate,
        SUM(CASE WHEN DataScadenza BETWEEN GETDATE() AND DATEADD(DAY, 30, GETDATE()) THEN 1 ELSE 0 END) AS prossimi_30gg
    FROM COGScadenze
    WHERE DataScadenza IS NOT NULL
");
print_r((array)$counts[0]);

echo "\n=== Prossime 5 scadenze 30gg ===\n";
$next = DB::connection('onda')->select("
    SELECT TOP 5 DataScadenza, Importo, CodTipDoc, NumDoc, IdAnag
    FROM COGScadenze
    WHERE DataScadenza BETWEEN GETDATE() AND DATEADD(DAY, 30, GETDATE())
    ORDER BY DataScadenza ASC
");
foreach ($next as $r) {
    echo "  {$r->DataScadenza} | €{$r->Importo} | {$r->CodTipDoc} {$r->NumDoc} | anag {$r->IdAnag}\n";
}
