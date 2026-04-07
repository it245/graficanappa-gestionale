<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tabelle = ['STDAnagrafiche', 'ATTDocCoda', 'ATTDocTeste', 'ATTDocRighe'];

foreach ($tabelle as $tabella) {
    echo "\n=== $tabella ===\n";
    $cols = DB::connection('onda')->select(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? ORDER BY ORDINAL_POSITION",
        [$tabella]
    );
    foreach ($cols as $c) {
        echo "  " . $c->COLUMN_NAME . "\n";
    }
}
