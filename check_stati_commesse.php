<?php
/**
 * Controlla lo stato di tutte le fasi per una lista di commesse.
 * Eseguire sul server: php check_stati_commesse.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
error_reporting(E_ALL & ~E_DEPRECATED);

use App\Models\OrdineFase;

$commesse = [
    '0066961-26', '0067042-26', '0067024-26', '0067028-26', '0066871-26',
    '0066909-26', '0067005-26', '0066843-26', '0067018-26', '0066840-26',
    '0066831-26', '0066842-26', '0066868-26', '0066867-26', '0067029-26',
];

foreach ($commesse as $c) {
    $fasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $c))
        ->with('faseCatalogo.reparto')
        ->orderBy('priorita')
        ->get();

    echo "\n=== $c ===\n";
    if ($fasi->isEmpty()) {
        echo "  Nessuna fase trovata\n";
        continue;
    }

    foreach ($fasi as $f) {
        $rep = $f->faseCatalogo->reparto->nome ?? '?';
        $stato = match((string) $f->stato) {
            '0' => '0 (non iniziata)',
            '1' => '1 (pronta)',
            '2' => '2 (avviata)',
            '3' => '3 (terminata)',
            '4' => '4 (consegnata)',
            '5' => '5 (esterno)',
            default => $f->stato,
        };
        $fine = $f->data_fine ? " fine: $f->data_fine" : '';
        echo "  $f->fase ($rep) -> $stato$fine\n";
    }
}

echo "\nCompletato.\n";
