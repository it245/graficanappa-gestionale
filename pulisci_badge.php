<?php
/**
 * Pulizia badge: rimuove vecchi dall'anagrafica,
 * merge timbrature per chi usa 2 badge (Crisanti, Pagano)
 * Eseguire su .60: php pulisci_badge.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Badge vecchi da rimuovere (non usati a marzo 2026)
$vecchi = [
    '000064', // BARBATO (usa 000059)
    '000049', // CARDILLO MARCO (usa 000045)
    '000042', // D'ORAZIO (usa 000651)
    '000017', // FRANCESE (usa 000658)
    '000050', // GARGIULO (usa 000653)
    '000052', // IULIANO (usa 000051)
    '000076', // MARRONE (usa 000668)
    '000018', // MENALE LUIGI (usa 000669)
    '000048', // RUSSO (usa 000044)
    '000038', // RAO (usa 000654)
    '000001', // SCARANO (usa 000039)
    '000060', // TORROMACCO (usa 000666)
    '000035', // VERDE (usa 000667)
    '000040', // ZAMPELLA (usa 000660)
];

echo "=== RIMOZIONE BADGE VECCHI DALL'ANAGRAFICA ===" . PHP_EOL;
foreach ($vecchi as $m) {
    $anag = DB::table('nettime_anagrafica')->where('matricola', $m)->first();
    if ($anag) {
        DB::table('nettime_anagrafica')->where('matricola', $m)->delete();
        echo "  Rimosso: $m ({$anag->cognome} {$anag->nome})" . PHP_EOL;
    } else {
        echo "  Non trovato: $m" . PHP_EOL;
    }
}

// Merge timbrature: chi usa 2 badge, sposta le timbrature del vecchio sul nuovo
$merge = [
    '000016' => '000664', // CRISANTI: vecchio 016 → nuovo 664
    '000024' => '000662', // PAGANO: vecchio 024 → nuovo 662... ma 024 ha PIU timbrature
];

// Crisanti: 664 è il principale (più recente e più timbrature)
// Pagano: 024 ha 37 timb vs 662 con 16 — 024 è il principale
// Invertiamo Pagano: teniamo 024 come attivo
echo PHP_EOL . "=== MERGE TIMBRATURE DOPPIO BADGE ===" . PHP_EOL;

// CRISANTI: merge 000016 → 000664
$count = DB::table('nettime_timbrature')
    ->where('matricola', '000016')
    ->whereNotExists(function ($q) {
        $q->select(DB::raw(1))
          ->from('nettime_timbrature as t2')
          ->whereColumn('t2.matricola', '=', DB::raw("'000664'"))
          ->whereColumn('t2.data_ora', '=', 'nettime_timbrature.data_ora')
          ->whereColumn('t2.verso', '=', 'nettime_timbrature.verso');
    })
    ->update(['matricola' => '000664']);
echo "  CRISANTI: $count timbrature spostate da 000016 → 000664" . PHP_EOL;
DB::table('nettime_anagrafica')->where('matricola', '000016')->delete();
echo "  Rimosso 000016 dall'anagrafica" . PHP_EOL;

// PAGANO: 000024 ha più timbrature, teniamo quello. Merge 000662 → 000024
$count = DB::table('nettime_timbrature')
    ->where('matricola', '000662')
    ->whereNotExists(function ($q) {
        $q->select(DB::raw(1))
          ->from('nettime_timbrature as t2')
          ->whereColumn('t2.matricola', '=', DB::raw("'000024'"))
          ->whereColumn('t2.data_ora', '=', 'nettime_timbrature.data_ora')
          ->whereColumn('t2.verso', '=', 'nettime_timbrature.verso');
    })
    ->update(['matricola' => '000024']);
echo "  PAGANO: $count timbrature spostate da 000662 → 000024" . PHP_EOL;
DB::table('nettime_anagrafica')->where('matricola', '000662')->delete();
echo "  Rimosso 000662 dall'anagrafica" . PHP_EOL;

// Rimuovo anche 000009 (mai usato)
DB::table('nettime_anagrafica')->where('matricola', '000009')->delete();
echo PHP_EOL . "Rimosso 000009 (mai usato)" . PHP_EOL;

// Conteggio finale
$tot = DB::table('nettime_anagrafica')->count();
echo PHP_EOL . "=== ANAGRAFICA FINALE: $tot dipendenti ===" . PHP_EOL;
$anag = DB::table('nettime_anagrafica')->orderBy('cognome')->get();
foreach ($anag as $a) {
    echo "  {$a->matricola}  {$a->cognome} {$a->nome}" . PHP_EOL;
}
