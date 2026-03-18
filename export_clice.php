<?php
/**
 * Esporta tutte le commesse con fase STAMPACALDOJOH dal MES.
 * Mostra: commessa, cliente, descrizione, codice fustella.
 *
 * Uso: php export_clice.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Helpers\DescrizioneParser;

// Trova tutte le commesse con fase stampa a caldo
$fasiJoh = OrdineFase::where('fase', 'LIKE', 'STAMPACALDOJOH%')
    ->with('ordine')
    ->get();

$risultati = [];

foreach ($fasiJoh as $f) {
    $o = $f->ordine;
    if (!$o) continue;

    $desc = $o->descrizione ?? '';
    $cliente = $o->cliente_nome ?? '';
    $notePre = $o->note_prestampa ?? '';
    $fsCodice = DescrizioneParser::parseFustella($desc, $cliente, $notePre);

    $risultati[] = [
        'commessa' => $o->commessa,
        'cliente' => $cliente,
        'descrizione' => $desc,
        'fustella' => $fsCodice ?: '-',
        'stato_fase' => $f->stato,
    ];
}

// Ordina per cliente poi fustella
usort($risultati, function($a, $b) {
    $cmp = strcmp($a['cliente'], $b['cliente']);
    return $cmp !== 0 ? $cmp : strcmp($a['fustella'], $b['fustella']);
});

// Output
echo "=== COMMESSE CON STAMPA A CALDO JOH ===\n";
echo "Totale: " . count($risultati) . "\n\n";

$clienteCorrente = '';
foreach ($risultati as $r) {
    if ($r['cliente'] !== $clienteCorrente) {
        $clienteCorrente = $r['cliente'];
        echo "\n--- {$clienteCorrente} ---\n";
    }
    $stato = ['Non iniziata','Pronto','Avviato','Terminato','Consegnato'];
    $statoLabel = isset($stato[$r['stato_fase']]) ? $stato[$r['stato_fase']] : $r['stato_fase'];
    echo "  {$r['commessa']} | FS: {$r['fustella']} | {$statoLabel} | " . substr($r['descrizione'], 0, 60) . "\n";
}

// Riepilogo fustelle uniche
$fsUniche = collect($risultati)->where('fustella', '!=', '-')->pluck('fustella')->unique()->sort();
echo "\n\n=== FUSTELLE UNICHE ({$fsUniche->count()}) ===\n";
foreach ($fsUniche as $fs) {
    $commesse = collect($risultati)->where('fustella', $fs);
    $clienti = $commesse->pluck('cliente')->unique()->implode(', ');
    echo "  {$fs} ({$commesse->count()} commesse) — {$clienti}\n";
}
