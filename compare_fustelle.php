<?php
/**
 * Confronto fustelle MES vs Onda
 * Eseguire sul server: php compare_fustelle.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CONFRONTO FUSTELLE MES vs ONDA ===\n\n";

// 1. Prendo le fustelle dal MES (dalla descrizione o dal campo fustella_codice)
$mesFustelle = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->whereNotNull('ordine_fasi.fustella_codice')
    ->where('ordine_fasi.fustella_codice', '!=', '')
    ->select('ordini.commessa', 'ordine_fasi.fustella_codice')
    ->distinct()
    ->get()
    ->groupBy('commessa');

echo "MES: " . $mesFustelle->count() . " commesse con fustella\n";

// 2. Prendo le fustelle da Onda (OC_ATTDocCartotecnica)
$onda = DB::connection('onda');

// Prima trovo la colonna PK di ATTDocTeste
$ondaFustelle = collect();
try {
    $rows = $onda->select("
        SELECT t.CodCommessa, c.CodNeutro, c.Resa
        FROM OC_ATTDocCartotecnica c
        JOIN ATTDocTeste t ON c.IdDoc = t.IdDoc
        WHERE c.CodNeutro IS NOT NULL AND c.CodNeutro != ''
        ORDER BY t.CodCommessa
    ");
    foreach ($rows as $row) {
        $ondaFustelle->push($row);
    }
    echo "Onda: " . $ondaFustelle->groupBy('CodCommessa')->count() . " commesse con fustella in OC_ATTDocCartotecnica\n\n";
} catch (\Exception $e) {
    echo "ERRORE Onda: " . $e->getMessage() . "\n";
    exit;
}

$ondaByCommessa = $ondaFustelle->groupBy('CodCommessa');

// 3. Confronto
$match = 0;
$mismatch = 0;
$soloMes = 0;
$soloOnda = 0;
$dettagliMismatch = [];

// Commesse presenti nel MES
foreach ($mesFustelle as $commessa => $fasi) {
    $mesCodici = $fasi->pluck('fustella_codice')->unique()->map(fn($c) => strtoupper(trim($c)))->values();

    // Cerco in Onda (formato commessa: 0066691-26)
    $ondaRows = $ondaByCommessa->get($commessa);
    if (!$ondaRows) {
        // Prova senza zero iniziale o con formato diverso
        $ondaRows = $ondaByCommessa->get(ltrim($commessa, '0'));
    }

    if ($ondaRows) {
        $ondaCodici = $ondaRows->pluck('CodNeutro')->unique()->map(fn($c) => strtoupper(trim($c)))->values();
        $mesSet = $mesCodici->sort()->values()->toArray();
        $ondaSet = $ondaCodici->sort()->values()->toArray();

        if ($mesSet == $ondaSet) {
            $match++;
        } else {
            $mismatch++;
            if (count($dettagliMismatch) < 30) {
                $dettagliMismatch[] = [
                    'commessa' => $commessa,
                    'mes' => implode(', ', $mesSet),
                    'onda' => implode(', ', $ondaSet),
                ];
            }
        }
    } else {
        $soloMes++;
        if ($soloMes <= 10) {
            echo "  Solo MES: $commessa => " . $mesCodici->implode(', ') . "\n";
        }
    }
}

// Commesse in Onda ma non nel MES
$mesCommesse = $mesFustelle->keys()->toArray();
foreach ($ondaByCommessa as $commessa => $rows) {
    if (!in_array($commessa, $mesCommesse)) {
        $soloOnda++;
    }
}

echo "\n--- RISULTATI ---\n";
echo "Corrispondenti:   $match\n";
echo "Diversi:          $mismatch\n";
echo "Solo MES:         $soloMes\n";
echo "Solo Onda:        $soloOnda (commesse non nel MES)\n";

if (count($dettagliMismatch) > 0) {
    echo "\n--- DETTAGLIO MISMATCH (primi 30) ---\n";
    echo str_pad('Commessa', 16) . str_pad('MES', 30) . "Onda\n";
    echo str_repeat('-', 80) . "\n";
    foreach ($dettagliMismatch as $d) {
        echo str_pad($d['commessa'], 16) . str_pad($d['mes'], 30) . $d['onda'] . "\n";
    }
}

// 4. Esempio: ultime 20 commesse Onda con fustella
echo "\n--- ULTIME 20 COMMESSE ONDA CON FUSTELLA ---\n";
$ultimeOnda = $onda->select("
    SELECT TOP 20 t.CodCommessa, c.CodNeutro, c.Resa,
           c.SuppFustellaBaseCM, c.SuppFustellaAltezzaCM, c.ResaFustella
    FROM OC_ATTDocCartotecnica c
    JOIN ATTDocTeste t ON c.IdDoc = t.IdDoc
    WHERE c.CodNeutro IS NOT NULL AND c.CodNeutro != ''
    ORDER BY t.CodCommessa DESC
");
echo str_pad('Commessa', 16) . str_pad('Fustella', 12) . str_pad('Resa', 6) . str_pad('ResaF', 8) . str_pad('Base', 8) . "Alt\n";
echo str_repeat('-', 60) . "\n";
foreach ($ultimeOnda as $r) {
    echo str_pad($r->CodCommessa, 16)
        . str_pad($r->CodNeutro, 12)
        . str_pad($r->Resa ?? '-', 6)
        . str_pad($r->ResaFustella ?? '-', 8)
        . str_pad($r->SuppFustellaBaseCM ?? '-', 8)
        . ($r->SuppFustellaAltezzaCM ?? '-') . "\n";
}

echo "\n=== FINE CONFRONTO ===\n";
