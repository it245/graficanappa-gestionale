<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$cliche = DB::table('cliche_anagrafica')->select('numero', 'descrizione_raw')->get();
echo "Cliché in anagrafica: " . count($cliche) . "\n\n";

// Ordini senza cliché
$ordini = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->whereIn('ordine_fasi.stato', ['0', '1'])
    ->whereNull('ordine_fasi.deleted_at')
    ->whereNull('ordini.cliche_numero')
    ->select('ordini.id', 'ordini.commessa', 'ordini.descrizione')
    ->distinct()
    ->get();

echo "Ordini senza cliché: " . count($ordini) . "\n\n";

function tokens($s) {
    $s = strtoupper($s);
    $s = preg_replace('/[^A-Z0-9 ]+/u', ' ', $s);
    $s = preg_replace('/(\d+)\s+(KG|GR|G)\b/u', '$1$2', $s);
    $stop = ['DI','LA','IL','E','DEL','DELLA','CON','AL','ALLA','DA','AST','ASTUCCIO','STAMPA','COLORI','DRIP','OFF','PLAST','FUSTELLATURA','INCOLLAGGIO','ORO','CALDO','RILIEVO'];
    return array_values(array_filter(preg_split('/\s+/', $s), fn($t) => $t !== '' && !in_array($t, $stop)));
}

$dettaglio = [];
foreach ($ordini as $o) {
    $tokOrd = tokens($o->descrizione ?? '');
    $best = null; $bestHit = 0;
    foreach ($cliche as $cl) {
        // ogni riga descrizione_raw può avere varianti per \n
        foreach (explode("\n", $cl->descrizione_raw) as $variant) {
            $tokCli = tokens($variant);
            if (count($tokCli) < 2) continue;
            $hit = count(array_intersect($tokOrd, $tokCli));
            if ($hit > $bestHit) {
                $best = ['numero' => $cl->numero, 'desc' => $variant, 'hit' => $hit, 'tot' => count($tokCli)];
                $bestHit = $hit;
            }
        }
    }
    $dettaglio[] = ['ordine' => $o, 'best' => $best];
}

// Raggruppa per esito
$matchabili = array_filter($dettaglio, fn($d) => $d['best'] && $d['best']['hit'] >= 2);
$noMatch = array_filter($dettaglio, fn($d) => !$d['best'] || $d['best']['hit'] < 2);

echo "=== POTENZIALI MATCH (>=2 token coincidenti, da rivedere) ===\n";
foreach ($matchabili as $d) {
    $o = $d['ordine']; $b = $d['best'];
    echo " {$o->commessa} ord {$o->id}: " . substr($o->descrizione, 0, 50) . "\n";
    echo "   -> cliché {$b['numero']} ({$b['hit']}/{$b['tot']} token): " . substr($b['desc'], 0, 50) . "\n";
}

echo "\n=== SENZA MATCH PROBABILE (cliché DA AGGIUNGERE) ===\n";
foreach ($noMatch as $d) {
    $o = $d['ordine'];
    echo " {$o->commessa} ord {$o->id}: " . substr($o->descrizione, 0, 80) . "\n";
}

echo "\nTotale matchabili: " . count($matchabili) . "\n";
echo "Totale da aggiungere: " . count($noMatch) . "\n";
