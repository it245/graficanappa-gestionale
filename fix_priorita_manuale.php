<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$updated = DB::table('ordine_fasi')
    ->where('priorita', '<', 0)
    ->where(function ($q) {
        $q->where('priorita_manuale', false)
          ->orWhereNull('priorita_manuale');
    })
    ->whereNull('deleted_at')
    ->update(['priorita_manuale' => true]);

echo "Aggiornate $updated fasi con priorità negativa → priorita_manuale = true" . PHP_EOL;
