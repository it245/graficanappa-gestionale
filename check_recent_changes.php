<?php
/**
 * Mostra le fasi modificate di recente (ultime N minuti).
 * Uso: php check_recent_changes.php [minuti=10]
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use Carbon\Carbon;

$minuti = (int) ($argv[1] ?? 10);
$da = Carbon::now()->subMinutes($minuti);

echo "=== Fasi modificate negli ultimi $minuti minuti (da " . $da->format('H:i:s') . ") ===\n\n";

$fasi = OrdineFase::where('updated_at', '>=', $da)
    ->with(['ordine', 'faseCatalogo'])
    ->orderBy('updated_at', 'desc')
    ->get();

if ($fasi->isEmpty()) {
    echo "Nessuna fase modificata negli ultimi $minuti minuti.\n";
    exit(0);
}

echo sprintf("%-8s %-16s %-22s %-7s %-10s %-20s\n",
    'ID', 'Commessa', 'Fase', 'Stato', 'Qta Prod', 'Modificata');
echo str_repeat('-', 90) . "\n";

foreach ($fasi as $f) {
    echo sprintf("%-8s %-16s %-22s %-7s %-10s %-20s\n",
        $f->id,
        $f->ordine->commessa ?? '?',
        $f->faseCatalogo->nome ?? $f->fase,
        $f->stato,
        $f->qta_prod ?? '-',
        Carbon::parse($f->updated_at)->format('d/m/Y H:i:s')
    );
}

echo "\nTotale: " . $fasi->count() . " fasi modificate.\n";
