<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$u = App\Models\Ordine::where('commessa', '0066624')->update(['commessa' => '0066624-26']);
echo "Ordini aggiornati: {$u}" . PHP_EOL;
