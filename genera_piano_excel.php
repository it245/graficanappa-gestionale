<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

error_reporting(E_ALL & ~E_DEPRECATED);

echo "Generazione piano produzione Excel...\n";
$path = 'C:\condivisa\mes\piano_produzione.xlsx';
App\Services\SchedulerExportService::export($path);
echo "Salvato: $path\n";
