<?php
/**
 * 1. Conta fasi stampa a caldo per stato
 * 2. Verifica perche la fase tagliacarte di 66964 torna dopo eliminazione
 *
 * Eseguire sul server: php check_caldo_e_66964.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
error_reporting(E_ALL & ~E_DEPRECATED);

use App\Models\OrdineFase;
use App\Models\Ordine;

// === 1. STAMPA A CALDO PER STATO ===
echo "=== STAMPA A CALDO PER STATO ===\n";
$count = OrdineFase::whereHas('faseCatalogo', fn($q) =>
    $q->whereHas('reparto', fn($r) => $r->where('nome', 'stampa a caldo'))
)->selectRaw('stato, COUNT(*) as cnt')
 ->groupBy('stato')
 ->pluck('cnt', 'stato');

$labels = ['0' => 'non iniziata', '1' => 'pronta', '2' => 'avviata', '3' => 'terminata', '4' => 'consegnata', '5' => 'esterno'];
foreach ($count as $s => $c) {
    $label = $labels[(string)$s] ?? $s;
    echo "  Stato $s ($label): $c\n";
}
echo "  Totale: " . $count->sum() . "\n";

// === 2. COMMESSA 66964 - TAGLIACARTE ===
echo "\n=== COMMESSA 0066964-26 ===\n";
$fasi = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', '0066964-26'))
    ->withTrashed() // include soft deleted
    ->with('faseCatalogo.reparto')
    ->orderBy('id')
    ->get();

foreach ($fasi as $f) {
    $rep = $f->faseCatalogo->reparto->nome ?? '?';
    $deleted = $f->deleted_at ? " ELIMINATA: $f->deleted_at" : '';
    echo "  ID:{$f->id} | {$f->fase} ($rep) | stato:{$f->stato}{$deleted}\n";
}

// Controlla se in Onda esiste un TAGLIACARTE per questa commessa
echo "\n=== CHECK ONDA per TAGLIACARTE ===\n";
try {
    $righe = DB::connection('onda')->select("
        SELECT f.CodFase, f.CodMacchina, f.QtaDaLavorare
        FROM ATTDocTeste t
        INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
        INNER JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
        WHERE t.CodCommessa = '0066964-26'
        ORDER BY f.NrRiga
    ");
    foreach ($righe as $r) {
        echo "  Onda: CodFase={$r->CodFase} Macchina={$r->CodMacchina} Qta={$r->QtaDaLavorare}\n";
    }
    if (empty($righe)) echo "  Nessuna fase trovata in Onda\n";
} catch (\Exception $e) {
    echo "  Errore Onda: " . $e->getMessage() . "\n";
}

echo "\nCompletato.\n";
