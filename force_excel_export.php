<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

App\Http\Services\ExcelSyncService::exportToExcel();
echo "Excel export completato.\n";
echo "Path: " . storage_path('app/excel_sync/dashboard_mes.xlsx') . "\n";
echo "Modified: " . date('Y-m-d H:i:s', filemtime(storage_path('app/excel_sync/dashboard_mes.xlsx'))) . "\n";
