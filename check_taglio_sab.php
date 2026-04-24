<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;

$count = OrdineFase::where('sched_macchina', 'TAGLIO')
    ->where('sched_inizio', 'like', '2026-04-25%')
    ->count();
echo "Tagliacarte sched sab 25/04: $count\n";

$count2 = OrdineFase::where('sched_macchina', 'TAGLIO')
    ->whereRaw('DAYOFWEEK(sched_inizio) IN (1,7)') // MySQL: 1=Sunday, 7=Saturday
    ->count();
echo "Tagliacarte sched weekend: $count2\n";

$count3 = OrdineFase::where('sched_macchina', 'XL106')
    ->where('sched_inizio', 'like', '2026-04-25%')
    ->count();
echo "XL106 sched sab 25/04: $count3\n";
