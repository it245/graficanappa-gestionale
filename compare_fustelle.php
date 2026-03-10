<?php
/**
 * Confronto fustelle MES vs Onda
 * MES: codice FS#### estratto dalla descrizione ordine
 * Onda: CodNeutro da OC_ATTDocCartotecnica
 * Eseguire sul server: php compare_fustelle.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Helpers\DescrizioneParser;

echo "=== CONFRONTO FUSTELLE MES vs ONDA ===\n\n";

// 1. Prendo le fustelle dal MES estraendole dalla descrizione ordine
$ordini = DB::table('ordini')
    ->select('commessa', 'descrizione', 'cliente_nome')
    ->whereNotNull('descrizione')
    ->get();

$mesFustelle = collect();
foreach ($ordini as $ordine) {
    $fs = DescrizioneParser::parseFustella($ordine->descrizione, $ordine->cliente_nome ?? '');
    if ($fs) {
        // parseFustella restituisce "FS0001 / FS0002" — splittiamo
        $codici = array_map('trim', explode('/', $fs));
        foreach ($codici as $codice) {
            $mesFustelle->push((object)[
                'commessa' => $ordine->commessa,
                'fustella' => strtoupper($codice),
            ]);
        }
    }
}

$mesByCommessa = $mesFustelle->groupBy('commessa');
echo "MES: " . $mesByCommessa->count() . " commesse con codice fustella (FS####) in descrizione\n";

// 2. Prendo le fustelle da Onda (OC_ATTDocCartotecnica)
$onda = DB::connection('onda');

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
    $ondaByCommessa = $ondaFustelle->groupBy('CodCommessa');
    echo "Onda: " . $ondaByCommessa->count() . " commesse con fustella in OC_ATTDocCartotecnica\n\n";
} catch (\Exception $e) {
    echo "ERRORE Onda: " . $e->getMessage() . "\n";
    exit;
}

// 3. Confronto
$match = 0;
$mismatch = 0;
$soloMes = 0;
$soloOnda = 0;
$dettagliMismatch = [];
$dettagliSoloMes = [];

foreach ($mesByCommessa as $commessa => $rows) {
    $mesCodici = $rows->pluck('fustella')->unique()->sort()->values()->toArray();

    // Cerco in Onda
    $ondaRows = $ondaByCommessa->get($commessa);

    if ($ondaRows) {
        $ondaCodici = $ondaRows->pluck('CodNeutro')->unique()
            ->map(fn($c) => strtoupper(trim($c)))->sort()->values()->toArray();

        if ($mesCodici == $ondaCodici) {
            $match++;
        } else {
            $mismatch++;
            if (count($dettagliMismatch) < 30) {
                $dettagliMismatch[] = [
                    'commessa' => $commessa,
                    'mes' => implode(', ', $mesCodici),
                    'onda' => implode(', ', $ondaCodici),
                ];
            }
        }
    } else {
        $soloMes++;
        if (count($dettagliSoloMes) < 15) {
            $dettagliSoloMes[] = "$commessa => " . implode(', ', $mesCodici);
        }
    }
}

// Commesse in Onda ma non nel MES
$mesCommesse = $mesByCommessa->keys()->toArray();
$dettagliSoloOnda = [];
foreach ($ondaByCommessa as $commessa => $rows) {
    if (!in_array($commessa, $mesCommesse)) {
        $soloOnda++;
        if (count($dettagliSoloOnda) < 15) {
            $codici = $rows->pluck('CodNeutro')->unique()->implode(', ');
            $dettagliSoloOnda[] = "$commessa => $codici";
        }
    }
}

echo "--- RISULTATI ---\n";
echo "Corrispondenti:   $match\n";
echo "Diversi:          $mismatch\n";
echo "Solo MES:         $soloMes (fustella in descrizione ma non in Onda cartotecnica)\n";
echo "Solo Onda:        $soloOnda (in Onda cartotecnica ma non nel MES)\n";

if (count($dettagliMismatch) > 0) {
    echo "\n--- DETTAGLIO DIVERSI (primi 30) ---\n";
    echo str_pad('Commessa', 16) . str_pad('MES (da descrizione)', 30) . "Onda (CodNeutro)\n";
    echo str_repeat('-', 80) . "\n";
    foreach ($dettagliMismatch as $d) {
        echo str_pad($d['commessa'], 16) . str_pad($d['mes'], 30) . $d['onda'] . "\n";
    }
}

if (count($dettagliSoloMes) > 0) {
    echo "\n--- SOLO MES (primi 15) ---\n";
    foreach ($dettagliSoloMes as $d) echo "  $d\n";
}

if (count($dettagliSoloOnda) > 0) {
    echo "\n--- SOLO ONDA (primi 15) ---\n";
    foreach ($dettagliSoloOnda as $d) echo "  $d\n";
}

// 4. Ultime 20 commesse Onda con fustella
echo "\n--- ULTIME 20 COMMESSE ONDA CON FUSTELLA ---\n";
$ultimeOnda = $onda->select("
    SELECT TOP 20 t.CodCommessa, c.CodNeutro, c.Resa
    FROM OC_ATTDocCartotecnica c
    JOIN ATTDocTeste t ON c.IdDoc = t.IdDoc
    WHERE c.CodNeutro IS NOT NULL AND c.CodNeutro != ''
    ORDER BY t.CodCommessa DESC
");
echo str_pad('Commessa', 16) . str_pad('Fustella', 12) . "Resa\n";
echo str_repeat('-', 40) . "\n";
foreach ($ultimeOnda as $r) {
    echo str_pad($r->CodCommessa, 16) . str_pad($r->CodNeutro, 12) . ($r->Resa ?? '-') . "\n";
}

echo "\n=== FINE CONFRONTO ===\n";
