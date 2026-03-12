<?php
// Elimina STAMPAXL106 dalla commessa 0066710-26 e ricalcola stati
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$d = App\Models\OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', '0066710-26'))
    ->where('fase', 'STAMPAXL106')
    ->delete();
echo "STAMPAXL106 eliminate: $d\n";

App\Services\FaseStatoService::ricalcolaCommessa('0066710-26');
echo "Stati ricalcolati.\n";
