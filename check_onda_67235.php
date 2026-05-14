<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== Sample PRDDocTeste (top 5) ===\n";
$sample = DB::connection('onda')->select("SELECT TOP 5 * FROM PRDDocTeste");
foreach ($sample as $r) {
    print_r((array)$r);
}

echo "\n=== Cerca commessa 67235 con LIKE ===\n";
$found = DB::connection('onda')->select(
    "SELECT TOP 30 IdDoc, CodArt, CodCommessa FROM PRDDocTeste WHERE CAST(CodCommessa AS VARCHAR) LIKE '%67235%'"
);
echo "Trovate: " . count($found) . "\n";
foreach ($found as $r) {
    echo " IdDoc={$r->IdDoc} CodCommessa=[{$r->CodCommessa}] CodArt={$r->CodArt}\n";
}
