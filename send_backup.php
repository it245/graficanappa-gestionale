<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Mail;

Mail::raw('Backup database MES - 10/03/2026', function($message) {
    $message->to('it@graficanappa.com')
            ->subject('Backup DB MES 10-03-2026')
            ->attach('C:\backup_db.sql');
});

echo "Email inviata!\n";
