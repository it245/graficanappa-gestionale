<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== REPARTI ===\n";
foreach (DB::table('reparti')->select('id','nome')->orderBy('nome')->get() as $r) {
    echo "  {$r->id} | '{$r->nome}'\n";
}
