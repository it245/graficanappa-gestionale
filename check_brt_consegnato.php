<?php
/**
 * Trova commesse con BRT a stato 3+ (terminato/consegnato) ma altre fasi ancora aperte.
 * Uso: php check_brt_consegnato.php [--fix] [--stato=3|4]
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ordine;
use App\Models\OrdineFase;

$fix = in_array('--fix', $argv);
$statoMin = 4; // default: solo stato 4
foreach ($argv as $a) {
    if (preg_match('/^--stato=(\d+)$/', $a, $m)) {
        $statoMin = (int) $m[1];
    }
}
echo "Cerco BRT con stato >= {$statoMin} e fasi aperte...\n\n";

// Tutte le commesse attive (con almeno 1 fase non consegnata)
$commesse = DB::table('ordini')
    ->join('ordine_fasi', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->whereNull('ordine_fasi.deleted_at')
    ->where('ordine_fasi.stato', '<', 4)
    ->distinct()
    ->pluck('ordini.commessa')
    ->sort()
    ->values();

$found = 0;

foreach ($commesse as $commessa) {
    $ordineIds = Ordine::where('commessa', $commessa)->pluck('id');

    $brtConsegnato = OrdineFase::whereIn('ordine_id', $ordineIds)
        ->whereIn('fase', ['BRT1', 'brt1', 'BRT'])
        ->whereNull('deleted_at')
        ->where('stato', '>=', $statoMin)
        ->exists();

    if (!$brtConsegnato) continue;

    $fasiAperte = OrdineFase::whereIn('ordine_id', $ordineIds)
        ->whereNull('deleted_at')
        ->where('stato', '<', 4)
        ->get();

    if ($fasiAperte->isEmpty()) continue;

    $found++;
    $brtStato = OrdineFase::whereIn('ordine_id', $ordineIds)
        ->whereIn('fase', ['BRT1', 'brt1', 'BRT'])
        ->whereNull('deleted_at')
        ->where('stato', '>=', $statoMin)
        ->value('stato');
    echo "COMMESSA: {$commessa} — BRT a stato {$brtStato}, ma {$fasiAperte->count()} fasi aperte:\n";
    foreach ($fasiAperte as $f) {
        echo "  fase #{$f->id} {$f->fase} stato={$f->stato}\n";
    }

    if ($fix) {
        OrdineFase::whereIn('ordine_id', $ordineIds)
            ->whereNull('deleted_at')
            ->where('stato', '<', 4)
            ->update(['stato' => 4, 'data_fine' => now()->format('Y-m-d H:i:s')]);
        echo "  → FIXATO: tutte le fasi portate a stato 4\n";
    }
    echo "\n";
}

echo str_repeat('=', 60) . "\n";
echo "Commesse con BRT>={$statoMin} e fasi aperte: {$found}\n";
if (!$fix && $found > 0) {
    echo "Usa --fix per portare tutte le fasi a stato 4\n";
}
