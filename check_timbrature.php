<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TIMBRATURE NETTIME ===\n\n";

// 1. Anagrafica: cerca badge 670, 671, 672 e i nomi
echo "--- 1. Anagrafica NetTime ---\n";
$anagrafica = DB::table('nettime_anagrafica')
    ->where(function ($q) {
        $q->whereIn('matricola', ['670', '671', '672'])
          ->orWhere(function ($q2) {
              $q2->whereRaw("LOWER(cognome) IN ('la scala', 'marcone', 'pietropaolo')");
          });
    })
    ->get();

if ($anagrafica->isEmpty()) {
    echo "  Nessun record trovato per badge 670/671/672 o La Scala/Marcone/Pietropaolo\n";

    // Mostra tutte le matricole per capire il formato
    echo "\n--- Ultime 20 matricole in anagrafica ---\n";
    $tutte = DB::table('nettime_anagrafica')->orderByDesc('id')->limit(20)->get();
    foreach ($tutte as $a) {
        echo "  Matricola={$a->matricola} {$a->cognome} {$a->nome}\n";
    }
} else {
    foreach ($anagrafica as $a) {
        echo "  Matricola={$a->matricola} {$a->cognome} {$a->nome}\n";
    }
}

// 2. Timbrature per questi badge (ultimi 7 giorni)
echo "\n--- 2. Timbrature ultime (badge 670/671/672) ---\n";
$timbrature = DB::table('nettime_timbrature')
    ->whereIn('matricola', ['670', '671', '672'])
    ->where('data_ora', '>=', now()->subDays(7))
    ->orderByDesc('data_ora')
    ->limit(30)
    ->get();

if ($timbrature->isEmpty()) {
    echo "  Nessuna timbratura per badge 670/671/672 negli ultimi 7 giorni\n";

    // Cerchiamo per nome
    echo "\n--- Timbrature per cognome ---\n";
    $matricole = DB::table('nettime_anagrafica')
        ->whereRaw("LOWER(cognome) IN ('la scala', 'marcone', 'pietropaolo')")
        ->pluck('matricola');

    if ($matricole->isNotEmpty()) {
        $timb = DB::table('nettime_timbrature')
            ->whereIn('matricola', $matricole)
            ->where('data_ora', '>=', now()->subDays(7))
            ->orderByDesc('data_ora')
            ->limit(30)
            ->get();
        foreach ($timb as $t) {
            echo "  Matricola={$t->matricola} Data={$t->data_ora} Tipo={$t->tipo}\n";
        }
        if ($timb->isEmpty()) {
            echo "  Nessuna timbratura per queste matricole\n";
        }
    } else {
        echo "  Matricole non trovate in anagrafica\n";
    }
} else {
    foreach ($timbrature as $t) {
        echo "  Matricola={$t->matricola} Data={$t->data_ora} Tipo={$t->tipo}\n";
    }
}

// 3. Statistiche generali
echo "\n--- 3. Statistiche timbrature ---\n";
$totale = DB::table('nettime_timbrature')->count();
$ultima = DB::table('nettime_timbrature')->max('data_ora');
$prima = DB::table('nettime_timbrature')->min('data_ora');
$oggi = DB::table('nettime_timbrature')->whereDate('data_ora', now()->toDateString())->count();
echo "  Totale: {$totale}\n";
echo "  Prima: {$prima}\n";
echo "  Ultima: {$ultima}\n";
echo "  Oggi: {$oggi}\n";

echo "\nDone.\n";
