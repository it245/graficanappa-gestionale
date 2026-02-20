<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

error_reporting(E_ALL & ~E_DEPRECATED);

try {
    \Maatwebsite\Excel\Facades\Excel::store(
        new \App\Exports\DashboardMesExport,
        'excel_sync/dashboard_mes.xlsx',
        'local'
    );
    echo "OK - file creato\n";
} catch (\Throwable $e) {
    echo "ERRORE: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
