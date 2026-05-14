<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$fasi = DB::table('ordine_fasi')->select('fase')->distinct()->where('fase', 'LIKE', '%SFUST%')->pluck('fase');
foreach ($fasi as $f) echo "$f\n";
