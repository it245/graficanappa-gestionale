<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$updated = App\Models\Ordine::where('commessa', '0066624')->update(['commessa' => '0066624-26']);
echo "Ordini aggiornati 0066624 → 0066624-26: {$updated}" . PHP_EOL;
echo "DONE" . PHP_EOL;
