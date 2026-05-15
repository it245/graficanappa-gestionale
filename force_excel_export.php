<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

App\Http\Services\ExcelSyncService::exportToExcel();
echo "Excel export completato.\n";

$path = env('EXCEL_SYNC_PATH') ?: storage_path('app/excel_sync');
$file = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . 'dashboard_mes.xlsx';
echo "Path: $file\n";
if (file_exists($file)) {
    echo "Modified: " . date('Y-m-d H:i:s', filemtime($file)) . "\n";
    echo "Size: " . round(filesize($file)/1024, 1) . " KB\n";
} else {
    echo "File NON esiste!\n";
}
