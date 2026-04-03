<?php
// Controlla commesse degli ultimi 10 giorni: PI01/FIN01 su Onda vs MES
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$giorni = 10;
$cutoff = now()->subDays($giorni)->format('Y-m-d');

echo "=== CHECK PI/FIN MANCANTI (ultimi {$giorni} giorni) ===\n\n";

// Commesse recenti attive
$commesse = DB::table('ordini')
    ->where('data_registrazione', '>=', $cutoff)
    ->distinct()
    ->pluck('commessa');

echo "Commesse recenti: {$commesse->count()}\n\n";

$problemi = [];

foreach ($commesse as $commessa) {
    // Conta PI01/FIN01 su Onda
    try {
        $fasiOnda = DB::connection('onda')->select("
            SELECT f.CodFase, COUNT(*) as cnt
            FROM PRDDocTeste p
            JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
            WHERE p.CodCommessa = ?
              AND f.CodFase IN ('PI01','PI02','PI03','FIN01','FIN03','FIN04')
            GROUP BY f.CodFase
        ", [$commessa]);
    } catch (\Exception $e) {
        continue;
    }

    if (empty($fasiOnda)) continue;

    foreach ($fasiOnda as $fo) {
        $countOnda = $fo->cnt;

        // Conta nel MES
        $countMes = DB::table('ordine_fasi')
            ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
            ->where('ordini.commessa', $commessa)
            ->where('ordine_fasi.fase', $fo->CodFase)
            ->whereNull('ordine_fasi.deleted_at')
            ->count();

        if ($countOnda > $countMes) {
            $problemi[] = [
                'commessa' => $commessa,
                'fase' => $fo->CodFase,
                'onda' => $countOnda,
                'mes' => $countMes,
                'mancanti' => $countOnda - $countMes,
            ];
        }
    }
}

if (empty($problemi)) {
    echo "Nessuna PI/FIN mancante!\n";
} else {
    echo str_pad('COMMESSA', 16) . str_pad('FASE', 8) . str_pad('ONDA', 6) . str_pad('MES', 6) . "MANCANTI\n";
    echo str_repeat('-', 50) . "\n";
    foreach ($problemi as $p) {
        echo str_pad($p['commessa'], 16) . str_pad($p['fase'], 8) . str_pad($p['onda'], 6) . str_pad($p['mes'], 6) . $p['mancanti'] . "\n";
    }
    echo "\nTotale commesse con fasi mancanti: " . count(array_unique(array_column($problemi, 'commessa'))) . "\n";
}
