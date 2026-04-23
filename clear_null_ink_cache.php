<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use Illuminate\Support\Facades\Cache;

$commesse = OrdineFase::where('stato', 3)
    ->where(function($q) {
        $q->whereHas('faseCatalogo.reparto', fn($r) => $r->whereRaw('LOWER(nome) = ?', ['stampa offset']))
          ->orWhere('fase', 'like', 'STAMPAXL106%');
    })
    ->with('ordine:id,commessa')
    ->get()
    ->pluck('ordine.commessa')
    ->filter()
    ->unique();

$null = 0; $valued = 0; $missing = 0;
foreach ($commesse as $c) {
    $k = "prinect_ink_total_{$c}";
    if (!Cache::has($k)) { $missing++; continue; }
    $v = Cache::get($k);
    if ($v === null || $v === 0) {
        Cache::forget($k);
        $null++;
    } else {
        $valued++;
    }
}
echo "Cache valued: $valued\nCache null (forget): $null\nCache missing: $missing\nTotal: " . $commesse->count() . "\n";
