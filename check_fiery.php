<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$n = DB::table('fiery_accounting')->count();
echo "Righe fiery_accounting: {$n}\n";

if ($n > 0) {
    $min = DB::table('fiery_accounting')->min('data_stampa');
    $max = DB::table('fiery_accounting')->max('data_stampa');
    echo "Periodo: {$min} → {$max}\n";

    $last = DB::table('fiery_accounting')->orderByDesc('data_stampa')->limit(5)->get(['data_stampa', 'commessa', 'copie', 'fogli', 'pagine_colore', 'pagine_bn']);
    echo "\nUltimi 5 job:\n";
    foreach ($last as $r) {
        echo "  {$r->data_stampa} commessa={$r->commessa} copie={$r->copie} fogli={$r->fogli} col={$r->pagine_colore} bn={$r->pagine_bn}\n";
    }

    $last7gg = DB::table('fiery_accounting')->where('data_stampa', '>=', now()->subDays(30))->count();
    echo "\nUltimi 30 giorni: {$last7gg} righe\n";
}
