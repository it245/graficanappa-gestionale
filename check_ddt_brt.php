<?php
/**
 * Controlla DDT BRT nel range specificato.
 * Mostra quali numeri DDT esistono nel MES e quali mancano.
 *
 * Uso: php check_ddt_brt.php [da] [a]
 * Es:  php check_ddt_brt.php 409 509
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ordine;

$da = (int) ($argv[1] ?? 409);
$a  = (int) ($argv[2] ?? 509);

echo "Controllo DDT nel range {$da} - {$a}\n";
echo str_repeat('=', 80) . "\n\n";

// DDT presenti nel MES
$ordiniConDDT = Ordine::whereNotNull('numero_ddt_vendita')
    ->where('numero_ddt_vendita', '!=', '')
    ->get()
    ->groupBy('numero_ddt_vendita');

// Mappa numero DDT → info
$ddtTrovati = [];
$ddtMancanti = [];

for ($n = $da; $n <= $a; $n++) {
    $numStr = (string) $n;
    // Cerca sia come numero puro che con zeri iniziali
    $found = $ordiniConDDT->filter(function ($group, $key) use ($numStr) {
        return ltrim($key, '0') === $numStr || $key === $numStr;
    });

    if ($found->isNotEmpty()) {
        foreach ($found as $ddtNum => $ordini) {
            $commesse = $ordini->pluck('commessa')->unique()->implode(', ');
            $vettore = $ordini->first()->vettore_ddt ?? '-';
            $ddtTrovati[$n] = [
                'ddt' => $ddtNum,
                'commesse' => $commesse,
                'vettore' => $vettore,
                'count' => $ordini->count(),
            ];
        }
    } else {
        $ddtMancanti[] = $n;
    }
}

echo "DDT TROVATI NEL MES:\n";
echo str_repeat('-', 80) . "\n";
foreach ($ddtTrovati as $n => $info) {
    $brt = stripos($info['vettore'], 'BRT') !== false ? 'BRT' : $info['vettore'];
    echo sprintf("  DDT %s → %s (%d ordini) [%s]\n", $info['ddt'], $info['commesse'], $info['count'], $brt);
}

echo "\n" . str_repeat('-', 80) . "\n";
echo "DDT MANCANTI NEL MES:\n";
if (empty($ddtMancanti)) {
    echo "  Nessuno!\n";
} else {
    echo "  " . implode(', ', $ddtMancanti) . "\n";
    echo "  Totale mancanti: " . count($ddtMancanti) . "\n";
}

// Controlla anche su Onda quali DDT esistono nel range
echo "\n" . str_repeat('=', 80) . "\n";
echo "CONTROLLO SU ONDA (ATTDocTeste TipoDocumento=3):\n";
echo str_repeat('-', 80) . "\n";

try {
    $righeOnda = DB::connection('onda')->select("
        SELECT t.NumeroDocumento, t.DataDocumento, t.IdDoc,
               v.RagioneSociale AS Vettore,
               r.CodCommessa
        FROM ATTDocTeste t
        JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
        LEFT JOIN ATTDocCoda c ON t.IdDoc = c.IdDoc
        LEFT JOIN STDAnagrafiche v ON c.IdVettore1 = v.IdAnagrafica
        WHERE t.TipoDocumento = 3
          AND CAST(t.NumeroDocumento AS INT) BETWEEN ? AND ?
          AND r.CodCommessa IS NOT NULL AND r.CodCommessa != ''
          AND r.TipoRiga = 1
        ORDER BY t.NumeroDocumento, r.CodCommessa
    ", [$da, $a]);

    if (empty($righeOnda)) {
        echo "  Nessun DDT trovato su Onda nel range {$da}-{$a}\n";
    } else {
        $ddtOnda = [];
        $perDDT = collect($righeOnda)->groupBy(fn($r) => (int) $r->NumeroDocumento);
        foreach ($perDDT as $num => $righe) {
            $vettore = trim($righe->first()->Vettore ?? '-');
            $data = $righe->first()->DataDocumento ?? '-';
            $commesse = $righe->pluck('CodCommessa')->unique()->implode(', ');
            $brt = stripos($vettore, 'BRT') !== false ? ' [BRT]' : '';
            echo sprintf("  DDT %d → data: %s, commesse: %s%s\n", $num, $data, $commesse, $brt);
            $ddtOnda[] = $num;
        }

        // DDT su Onda ma non nel MES
        $soloOnda = array_diff($ddtOnda, array_keys($ddtTrovati));
        if (!empty($soloOnda)) {
            echo "\n  DDT su Onda ma NON nel MES: " . implode(', ', $soloOnda) . "\n";
        }
    }
} catch (\Exception $e) {
    echo "  Errore connessione Onda: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "Riepilogo: trovati " . count($ddtTrovati) . "/" . ($a - $da + 1) . " DDT nel MES\n";
