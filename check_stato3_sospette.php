<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use App\Models\PrinectAttivita;

echo "=== ANALISI FASI STAMPA XL A STATO 3 ===" . PHP_EOL;
echo "Escluse: chiuse il 27/02/2026 (chiuse manualmente)" . PHP_EOL . PHP_EOL;

$fasi = OrdineFase::with('ordine', 'operatori')
    ->where('stato', 3)
    ->where(function ($q) {
        $q->where('fase', 'LIKE', 'STAMPAXL106%')
          ->orWhere('fase', 'STAMPA')
          ->orWhere('fase', 'LIKE', 'STAMPA XL%');
    })
    // Escludi chiuse il 27/02
    ->where(function ($q) {
        $q->whereNull('data_fine')
          ->orWhere('data_fine', '<', '2026-02-27 14:30:00')
          ->orWhere('data_fine', '>', '2026-02-27 14:40:00');
    })
    ->get();

echo "Totale fasi trovate: " . $fasi->count() . PHP_EOL . PHP_EOL;

$sospette = [];
$ok = [];

foreach ($fasi as $f) {
    $commessa = $f->ordine->commessa ?? '?';
    $cliente = $f->ordine->cliente_nome ?? '?';
    $qtaCarta = $f->ordine->qta_carta ?? 0;
    $hasOperatori = $f->operatori->count() > 0;
    $hasDataInizio = !empty($f->data_inizio);
    $hasDataFine = !empty($f->data_fine);
    $fogliBuoni = $f->fogli_buoni ?? 0;
    $fogliScarto = $f->fogli_scarto ?? 0;

    // Attività Prinect
    $attivitaCount = PrinectAttivita::where('commessa_gestionale', $commessa)->count();
    $attivitaBuoni = PrinectAttivita::where('commessa_gestionale', $commessa)->sum('fogli_buoni');

    $problemi = [];

    // Check 1: nessun operatore
    if (!$hasOperatori) $problemi[] = "NO OPERATORI";

    // Check 2: data_inizio mancante
    if (!$hasDataInizio) $problemi[] = "NO DATA_INIZIO";

    // Check 3: 0 fogli buoni
    if ($fogliBuoni <= 0) $problemi[] = "0 FOGLI_BUONI";

    // Check 4: 0 attività Prinect
    if ($attivitaCount == 0) $problemi[] = "0 ATTIVITA_PRINECT";

    // Check 5: fogli buoni molto inferiori a qta_carta (< 50%)
    if ($qtaCarta > 0 && $fogliBuoni > 0 && $fogliBuoni < $qtaCarta * 0.5) {
        $pct = round($fogliBuoni / $qtaCarta * 100);
        $problemi[] = "FOGLI_BASSI ({$pct}% di {$qtaCarta})";
    }

    // Check 6: fogli da attività molto diversi da fogli in fase
    if ($attivitaBuoni > 0 && $fogliBuoni > 0 && abs($fogliBuoni - $attivitaBuoni) > $fogliBuoni * 0.5) {
        $problemi[] = "FOGLI_DISCREPANZA (fase:{$fogliBuoni} vs att:{$attivitaBuoni})";
    }

    $record = [
        'commessa' => $commessa,
        'cliente' => substr($cliente, 0, 30),
        'fase' => $f->fase,
        'fogli_buoni' => $fogliBuoni,
        'qta_carta' => $qtaCarta,
        'operatori' => $hasOperatori ? $f->operatori->pluck('nome')->implode(', ') : '-',
        'data_inizio' => $f->data_inizio ?? '-',
        'data_fine' => $f->data_fine ?? '-',
        'attivita_prinect' => $attivitaCount,
        'att_buoni' => $attivitaBuoni,
        'problemi' => $problemi,
    ];

    if (!empty($problemi)) {
        $sospette[] = $record;
    } else {
        $ok[] = $record;
    }
}

// Ordina sospette per numero problemi (più gravi prima)
usort($sospette, fn($a, $b) => count($b['problemi']) - count($a['problemi']));

echo "========================================" . PHP_EOL;
echo "SOSPETTE: " . count($sospette) . " fasi con problemi" . PHP_EOL;
echo "========================================" . PHP_EOL . PHP_EOL;

foreach ($sospette as $s) {
    $prob = implode(' | ', $s['problemi']);
    echo "[" . count($s['problemi']) . " PROBLEMI] {$s['commessa']} | {$s['cliente']}" . PHP_EOL;
    echo "  Fase: {$s['fase']} | Fogli: {$s['fogli_buoni']}/{$s['qta_carta']} | Op: {$s['operatori']}" . PHP_EOL;
    echo "  Inizio: {$s['data_inizio']} | Fine: {$s['data_fine']}" . PHP_EOL;
    echo "  Attività Prinect: {$s['attivita_prinect']} (buoni: {$s['att_buoni']})" . PHP_EOL;
    echo "  >> {$prob}" . PHP_EOL . PHP_EOL;
}

echo "========================================" . PHP_EOL;
echo "OK: " . count($ok) . " fasi senza problemi" . PHP_EOL;
echo "========================================" . PHP_EOL;
