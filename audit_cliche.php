<?php
/**
 * Audit cliché: stato match su tutti ordini attivi.
 * - Ordini SENZA cliché (potenziali mancanti)
 * - Ordini con cliché manuale (review)
 * - Cliché anagrafica mai usati
 * - Conteggi per tipo match
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Ordini "attivi" = almeno 1 fase stato 0-1
$ordiniIds = DB::table('ordine_fasi')
    ->whereIn('stato', ['0','1','2'])
    ->whereNull('deleted_at')
    ->distinct()
    ->pluck('ordine_id');

$ordini = DB::table('ordini')
    ->whereIn('id', $ordiniIds)
    ->select('id', 'commessa', 'descrizione', 'cliche_numero', 'cliche_match_type')
    ->get();

echo "Ordini attivi totali: " . count($ordini) . "\n\n";

// Statistiche match
$stats = ['con_cliche' => 0, 'senza_cliche' => 0, 'auto' => 0, 'manual' => 0];
foreach ($ordini as $o) {
    if ($o->cliche_numero) {
        $stats['con_cliche']++;
        if ($o->cliche_match_type === 'manual') $stats['manual']++;
        elseif ($o->cliche_match_type === 'auto') $stats['auto']++;
    } else {
        $stats['senza_cliche']++;
    }
}

echo "=== STATISTICHE ===\n";
echo "Con cliché:   {$stats['con_cliche']}\n";
echo "  Auto:       {$stats['auto']}\n";
echo "  Manuale:    {$stats['manual']}\n";
echo "Senza cliché: {$stats['senza_cliche']}\n\n";

// Ordini senza cliché (top 30 per debug)
echo "=== ORDINI SENZA CLICHE' (top 30) ===\n";
$senza = $ordini->where('cliche_numero', null)->take(30);
foreach ($senza as $o) {
    echo "  {$o->commessa} | " . substr($o->descrizione ?? '', 0, 80) . "\n";
}

// Cliché anagrafica MAI usati
echo "\n=== CLICHE' ANAGRAFICA MAI USATI ===\n";
$usati = $ordini->pluck('cliche_numero')->filter()->unique();
$tutti = DB::table('cliche_anagrafica')->pluck('descrizione_raw', 'numero');
$mai_usati = [];
foreach ($tutti as $num => $desc) {
    if (!$usati->contains($num)) {
        $mai_usati[$num] = $desc;
    }
}
echo "Totale mai usati: " . count($mai_usati) . " / " . count($tutti) . "\n";
echo "Top 20:\n";
foreach (array_slice($mai_usati, 0, 20, true) as $num => $desc) {
    echo "  $num → $desc\n";
}

// Cliché USATI da >1 ordine in stessa commessa (potenziali bug multi-modello rimasti)
echo "\n=== CLICHE' DUPLICATI IN STESSA COMMESSA ===\n";
$dup = DB::table('ordini')
    ->select('commessa', 'cliche_numero', DB::raw('COUNT(*) as n'))
    ->whereNotNull('cliche_numero')
    ->whereIn('id', $ordiniIds)
    ->groupBy('commessa', 'cliche_numero')
    ->having('n', '>', 1)
    ->get();
echo "Commesse con duplicati: " . count($dup) . "\n";
foreach ($dup->take(20) as $d) {
    echo "  {$d->commessa} cliché {$d->cliche_numero} ({$d->n} ordini)\n";
}
