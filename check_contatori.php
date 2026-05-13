<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== Contatori Stampante: ultimi 15 snapshot ===\n";
$rows = DB::table('contatori_stampante')
    ->orderBy('rilevato_at', 'desc')
    ->limit(15)
    ->get();

if ($rows->isEmpty()) { echo "  Nessun record.\n"; exit; }

echo sprintf("%-20s %-15s %-10s %-12s %-12s %-12s %-12s\n",
    'rilevato_at', 'stampante', 'totale_1', 'nero_grande', 'nero_piccolo', 'colore_grande', 'colore_piccolo');
echo str_repeat('-', 105) . "\n";
foreach ($rows as $r) {
    echo sprintf("%-20s %-15s %-10s %-12s %-12s %-12s %-12s\n",
        substr($r->rilevato_at, 0, 19),
        substr($r->stampante ?? '-', 0, 15),
        $r->totale_1 ?? '-',
        $r->nero_grande ?? '-',
        $r->nero_piccolo ?? '-',
        $r->colore_grande ?? '-',
        $r->colore_piccolo ?? '-'
    );
}

echo "\n=== Gap snapshot (giorni feriali mancanti ultimi 30 gg) ===\n";
$inizio = now()->subDays(30);
$fine = now();
$snapshotDates = DB::table('contatori_stampante')
    ->whereBetween('rilevato_at', [$inizio, $fine])
    ->selectRaw('DATE(rilevato_at) as g')
    ->distinct()
    ->pluck('g')
    ->toArray();
$mancanti = [];
$cur = $inizio->copy()->startOfDay();
while ($cur <= $fine) {
    if (!$cur->isWeekend() && !in_array($cur->format('Y-m-d'), $snapshotDates)) {
        $mancanti[] = $cur->format('Y-m-d') . ' (' . $cur->locale('it')->dayName . ')';
    }
    $cur->addDay();
}
if (empty($mancanti)) echo "  Tutti i giorni feriali coperti ✓\n";
else echo "  Mancanti: " . implode(', ', $mancanti) . "\n";

echo "\n=== Conteggio totale per stampante ===\n";
$tot = DB::table('contatori_stampante')
    ->selectRaw('stampante, COUNT(*) as n, MIN(rilevato_at) as primo, MAX(rilevato_at) as ultimo')
    ->groupBy('stampante')
    ->get();
foreach ($tot as $t) {
    echo "  {$t->stampante}: {$t->n} snapshot — dal " . substr($t->primo, 0, 16) . " al " . substr($t->ultimo, 0, 16) . "\n";
}
