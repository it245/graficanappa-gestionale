<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== Confronto fasi Piegaincolla: ONDA vs MES ===\n";
echo "(solo commesse con stato fasi attive: 0/1/2 nel MES)\n\n";

// Step 1: commesse attive nel MES con almeno 1 PI
$commesseMes = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->whereIn('ordine_fasi.fase', ['PI01','PI02','PI03'])
    ->whereNull('ordine_fasi.deleted_at')
    ->select('ordini.commessa')
    ->distinct()
    ->pluck('commessa')
    ->toArray();

echo "Commesse con almeno 1 PI in MES: " . count($commesseMes) . "\n\n";

$mancanti = [];

foreach ($commesseMes as $comm) {
    // Conteggio MES (fasi attive 0/1/2 di tipo PI)
    $mesCount = DB::table('ordine_fasi')
        ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
        ->where('ordini.commessa', $comm)
        ->whereIn('ordine_fasi.fase', ['PI01','PI02','PI03'])
        ->whereNull('ordine_fasi.deleted_at')
        ->count();

    // Conteggio ONDA (fasi PI distinte = 1 per PRD)
    $ondaCount = DB::connection('onda')->selectOne(
        "SELECT COUNT(*) AS n
         FROM PRDDocTeste p
         INNER JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
         WHERE p.CodCommessa = ?
           AND f.CodFase IN ('PI01','PI02','PI03')",
        [$comm]
    )->n ?? 0;

    if ($ondaCount > $mesCount) {
        $mancanti[] = [
            'commessa' => $comm,
            'mes' => $mesCount,
            'onda' => $ondaCount,
            'mancanti' => $ondaCount - $mesCount,
        ];
    }
}

if (empty($mancanti)) {
    echo "✓ Nessuna commessa con PI mancanti nel MES.\n";
    exit;
}

usort($mancanti, fn($a, $b) => $b['mancanti'] <=> $a['mancanti']);

echo sprintf("%-13s %5s %5s %5s\n", 'commessa', 'MES', 'ONDA', 'manc');
echo str_repeat('-', 35) . "\n";
foreach ($mancanti as $m) {
    echo sprintf("%-13s %5d %5d %5d\n", $m['commessa'], $m['mes'], $m['onda'], $m['mancanti']);
}

echo "\nTotale commesse con PI mancanti: " . count($mancanti) . "\n";
echo "Totale fasi PI mancanti: " . array_sum(array_column($mancanti, 'mancanti')) . "\n";
