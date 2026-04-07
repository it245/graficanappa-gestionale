<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

error_reporting(E_ALL & ~E_DEPRECATED);

$fasi = App\Models\OrdineFase::whereNotNull('sched_fine')
    ->where('stato', '<', 3)
    ->with('ordine')
    ->orderBy('sched_fine')
    ->get();

echo "=== PIANO SCHEDULER PER GIORNO ===\n";
$perGiorno = $fasi->groupBy(fn($f) => \Carbon\Carbon::parse($f->sched_fine)->format('Y-m-d (D)'));

foreach ($perGiorno as $data => $gruppo) {
    $spedizioni = $gruppo->filter(fn($f) => $f->fase === 'BRT1');
    $commesseSpedibili = $spedizioni->map(fn($f) => $f->ordine->commessa ?? '?')->unique();
    echo "\n$data: {$gruppo->count()} fasi, {$spedizioni->count()} BRT pronte\n";
    if ($commesseSpedibili->isNotEmpty()) {
        foreach ($commesseSpedibili as $c) {
            echo "  → $c\n";
        }
    }
}

echo "\n=== COMMESSE CON CONSEGNA GIOVEDI/VENERDI (10-11 aprile) ===\n";
$urgenti = App\Models\Ordine::whereBetween('data_prevista_consegna', ['2026-04-10', '2026-04-11'])
    ->whereHas('fasi', fn($q) => $q->where('stato', '<', 3))
    ->with('fasi')
    ->get();

foreach ($urgenti as $o) {
    $totFasi = $o->fasi->count();
    $terminate = $o->fasi->filter(fn($f) => is_numeric($f->stato) && (int)$f->stato >= 3 && (int)$f->stato != 5)->count();
    echo "  {$o->commessa} | {$o->descrizione} | Consegna: " . ($o->data_prevista_consegna ? \Carbon\Carbon::parse($o->data_prevista_consegna)->format('d/m') : '?') . " | Fasi: $terminate/$totFasi\n";
}
