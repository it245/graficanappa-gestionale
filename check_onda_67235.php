<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::connection('onda')->select("
    SELECT TOP 50 PRDDocumento, PRDArtigo, PRDDescricao, PRDQtt, PRDQttDdt, PRDDataDdt
    FROM PRDDocTeste
    WHERE PRDDocumento LIKE '%67235%'
    ORDER BY PRDDocumento
");

echo "PRD Onda per 67235:\n";
foreach ($rows as $r) {
    echo " {$r->PRDDocumento} | {$r->PRDArtigo} | qta={$r->PRDQtt} ddt={$r->PRDQttDdt} | " . substr($r->PRDDescricao ?? '', 0, 80) . "\n";
}
echo "\nTotale: " . count($rows) . " PRD\n";
