<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = $argv[1] ?? null;
if (!$commessa) { echo "Uso: php elimina_commessa.php 0066716-26" . PHP_EOL; exit(1); }

// Aggiungi -26 se mancante
if (!preg_match('/-\d{2}$/', $commessa)) {
    $commessa = str_pad($commessa, 7, '0', STR_PAD_LEFT) . '-' . date('y');
}

$ordini = App\Models\Ordine::where('commessa', $commessa)->get();
if ($ordini->isEmpty()) { echo "Commessa {$commessa} non trovata." . PHP_EOL; exit(1); }

echo "=== ELIMINA COMMESSA {$commessa} ===" . PHP_EOL;
$totFasi = 0;
foreach ($ordini as $o) {
    $fasi = App\Models\OrdineFase::where('ordine_id', $o->id)->get();
    echo "  Ordine ID:{$o->id} | {$o->cod_art} | fasi:{$fasi->count()}" . PHP_EOL;
    foreach ($fasi as $f) {
        echo "    ELIMINA fase: {$f->fase} | stato:{$f->stato}" . PHP_EOL;
        $f->operatori()->detach();
        $f->delete();
        $totFasi++;
    }
    $o->delete();
}
echo PHP_EOL . "Eliminati: {$ordini->count()} ordini, {$totFasi} fasi" . PHP_EOL;
