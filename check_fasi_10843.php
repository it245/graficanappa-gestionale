<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

foreach ([10840, 10843] as $id) {
    echo "=== Ordine $id ===\n";
    $fasi = DB::table('ordine_fasi')
        ->where('ordine_id', $id)
        ->whereNull('deleted_at')
        ->select('id','fase','stato','qta_prod')
        ->get();
    echo "Fasi attive: " . count($fasi) . "\n";
    foreach ($fasi as $f) {
        echo "  fase_id={$f->id} {$f->fase} stato={$f->stato} qta={$f->qta_prod}\n";
    }
    echo "\n";
}
