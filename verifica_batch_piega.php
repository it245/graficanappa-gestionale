<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Cerca commesse ITALIANA CONFETTI con "AST. 1 KG" (= FS0898 dal parser)
// che hanno fase piegaincolla (PI01/PI02/PI03)

echo "=== COMMESSE ITALIANA CONFETTI FS0898 (AST. 1 KG) + PIEGAINCOLLA ===" . PHP_EOL . PHP_EOL;

$fasi = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->where('ordini.cliente_nome', 'LIKE', '%ITALIANA CONFETTI%')
    ->where('ordini.descrizione', 'LIKE', 'AST%1 KG%')
    ->whereIn('ordine_fasi.fase', ['PI01', 'PI02', 'PI03'])
    ->whereNull('ordine_fasi.deleted_at')
    ->where('ordine_fasi.stato', '<', 3)
    ->select(
        'ordini.commessa', 'ordini.cod_art', 'ordini.descrizione',
        'ordini.qta_richiesta', 'ordini.data_prevista_consegna',
        'ordine_fasi.fase', 'ordine_fasi.qta_fase', 'ordine_fasi.stato'
    )
    ->orderBy('ordini.cod_art')
    ->orderBy('ordini.commessa')
    ->get();

if ($fasi->isEmpty()) {
    echo "Nessuna fase trovata. Provo ricerca più ampia..." . PHP_EOL;

    // Cerca tutte le commesse con FS0898 nella descrizione
    $ordini = DB::table('ordini')
        ->where('cliente_nome', 'LIKE', '%ITALIANA CONFETTI%')
        ->where('descrizione', 'LIKE', '%FS0898%')
        ->select('commessa', 'cod_art', 'descrizione', 'qta_richiesta', 'data_prevista_consegna')
        ->orderBy('cod_art')
        ->get();

    echo "Ordini con FS0898:" . PHP_EOL;
    foreach ($ordini as $o) {
        $desc = mb_substr($o->descrizione, 0, 60);
        echo "  {$o->commessa} | {$o->cod_art} | {$desc} | qta:{$o->qta_richiesta} | cons:{$o->data_prevista_consegna}" . PHP_EOL;

        // Cerca le fasi piega per questo ordine
        $piega = DB::table('ordine_fasi')
            ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
            ->where('ordini.commessa', $o->commessa)
            ->whereIn('ordine_fasi.fase', ['PI01', 'PI02', 'PI03'])
            ->whereNull('ordine_fasi.deleted_at')
            ->select('ordine_fasi.fase', 'ordine_fasi.qta_fase', 'ordine_fasi.stato')
            ->get();

        if ($piega->isEmpty()) {
            echo "    → Nessuna fase piega" . PHP_EOL;
        } else {
            foreach ($piega as $p) {
                echo "    → {$p->fase} | qta_fase:{$p->qta_fase} | stato:{$p->stato}" . PHP_EOL;
            }
        }
    }

    // Raggruppa per cod_art
    echo PHP_EOL . "=== RAGGRUPPAMENTO PER ARTICOLO ===" . PHP_EOL;
    $perArt = $ordini->groupBy('cod_art');
    foreach ($perArt as $art => $gruppo) {
        if ($gruppo->count() > 1) {
            echo PHP_EOL . "ARTICOLO: $art ({$gruppo->count()} commesse)" . PHP_EOL;
            foreach ($gruppo as $o) {
                echo "  {$o->commessa} | qta:{$o->qta_richiesta} | cons:{$o->data_prevista_consegna}" . PHP_EOL;
            }
        }
    }
} else {
    // Raggruppa per cod_art + fase
    echo str_pad('COMMESSA', 16) . str_pad('COD ART', 25) . str_pad('FASE', 6) . str_pad('QTA', 10) . str_pad('STATO', 6) . str_pad('CONSEGNA', 12) . "DESCRIZIONE" . PHP_EOL;
    echo str_repeat('-', 120) . PHP_EOL;

    $perArt = [];
    foreach ($fasi as $f) {
        $desc = mb_substr($f->descrizione, 0, 40);
        echo str_pad($f->commessa, 16) . str_pad($f->cod_art, 25) . str_pad($f->fase, 6) . str_pad($f->qta_fase, 10) . str_pad($f->stato, 6) . str_pad($f->data_prevista_consegna ?? '-', 12) . $desc . PHP_EOL;
        $perArt[$f->cod_art][] = $f;
    }

    echo PHP_EOL . "=== STESSO ARTICOLO, COMMESSE DIVERSE ===" . PHP_EOL;
    foreach ($perArt as $art => $gruppo) {
        if (count($gruppo) > 1) {
            echo PHP_EOL . "ARTICOLO: $art — {$gruppo[0]->fase} ({count($gruppo)} commesse) → BATCH POSSIBILE!" . PHP_EOL;
            foreach ($gruppo as $f) {
                echo "  {$f->commessa} | qta:{$f->qta_fase} | stato:{$f->stato} | cons:{$f->data_prevista_consegna}" . PHP_EOL;
            }
        }
    }
}
