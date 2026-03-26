<?php
// Verifica percentuali kiosk: mostra il calcolo esatto per ogni reparto
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$oggi = date('Y-m-d');
$oraCorrente = (float)date('H') + (float)date('i') / 60;
echo "=== VERIFICA PERCENTUALI KIOSK — {$oggi} ore " . date('H:i') . " ===\n\n";

$reparti = [
    ['nome' => 'XL 106 (16h)', 'db' => ['stampa offset'], 'ore_disp' => 16, 'inizio' => 6],
    ['nome' => 'BOBST', 'db' => ['fustella piana'], 'ore_disp' => 16, 'inizio' => 6],
    ['nome' => 'JOH Caldo', 'db' => ['stampa a caldo'], 'ore_disp' => 16, 'inizio' => 6],
    ['nome' => 'Plastificatrice', 'db' => ['plastificazione'], 'ore_disp' => 8, 'inizio' => 8],
    ['nome' => 'Piegaincolla', 'db' => ['piegaincolla'], 'ore_disp' => 14, 'inizio' => 6],
    ['nome' => 'Finestratrice', 'db' => ['finestratura'], 'ore_disp' => 14, 'inizio' => 6],
    ['nome' => 'Fustella Cilindrica', 'db' => ['fustella cilindrica'], 'ore_disp' => 8, 'inizio' => 8],
    ['nome' => 'Canon V900', 'db' => ['digitale', 'finitura digitale'], 'ore_disp' => 8, 'inizio' => 8],
    ['nome' => 'Tagliacarte', 'db' => ['tagliacarte'], 'ore_disp' => 8, 'inizio' => 8],
    ['nome' => 'Legatoria', 'db' => ['legatoria'], 'ore_disp' => 14, 'inizio' => 6],
];

foreach ($reparti as $ru) {
    $repartoIds = DB::table('reparti')->whereIn('nome', $ru['db'])->pluck('id');
    if ($repartoIds->isEmpty()) continue;

    $inizioTurno = $oggi . ' ' . str_pad($ru['inizio'], 2, '0', STR_PAD_LEFT) . ':00:00';
    $oreDispOra = max(min($oraCorrente - $ru['inizio'], $ru['ore_disp']), 0.5);

    // Fasi in corso (stato 2)
    $aperte = DB::table('ordine_fasi as f')
        ->join('ordini as o', 'f.ordine_id', '=', 'o.id')
        ->join('fasi_catalogo as fc', 'f.fase_catalogo_id', '=', 'fc.id')
        ->whereIn('fc.reparto_id', $repartoIds)
        ->where('f.stato', 2)
        ->select('f.id', 'f.fase', 'f.data_inizio', 'o.commessa')
        ->get();

    // Fasi terminate oggi (stato 3, data_fine oggi)
    $chiuse = DB::table('ordine_fasi as f')
        ->join('ordini as o', 'f.ordine_id', '=', 'o.id')
        ->join('fasi_catalogo as fc', 'f.fase_catalogo_id', '=', 'fc.id')
        ->whereIn('fc.reparto_id', $repartoIds)
        ->where('f.stato', 3)
        ->whereDate('f.data_fine', $oggi)
        ->select('f.id', 'f.fase', 'f.data_inizio', 'f.data_fine', 'o.commessa')
        ->get();

    echo "--- {$ru['nome']} (turno {$ru['inizio']}:00, disp finora: " . round($oreDispOra, 1) . "h) ---\n";

    $totSec = 0;

    if ($aperte->isNotEmpty()) {
        echo "  IN CORSO (stato 2):\n";
        foreach ($aperte as $f) {
            $start = max(strtotime($f->data_inizio ?: $inizioTurno), strtotime($inizioTurno));
            $sec = max(time() - $start, 0);
            $ore = round($sec / 3600, 2);
            $totSec += $sec;
            echo "    {$f->commessa} | {$f->fase} | inizio:{$f->data_inizio} | da turno: {$ore}h\n";
        }
    }

    if ($chiuse->isNotEmpty()) {
        echo "  CHIUSE OGGI (stato 3):\n";
        foreach ($chiuse as $f) {
            $start = max(strtotime($f->data_inizio ?: $inizioTurno), strtotime($inizioTurno));
            $end = strtotime($f->data_fine);
            $sec = max($end - $start, 0);
            $ore = round($sec / 3600, 2);
            $totSec += $sec;
            echo "    {$f->commessa} | {$f->fase} | {$f->data_inizio} → {$f->data_fine} | {$ore}h\n";
        }
    }

    if ($aperte->isEmpty() && $chiuse->isEmpty()) {
        echo "  Nessuna fase attiva o chiusa oggi\n";
    }

    $totOre = round($totSec / 3600, 2);
    $pct = $oreDispOra > 0 ? min(round(($totOre / $oreDispOra) * 100), 100) : 0;
    echo "  TOTALE: {$totOre}h / " . round($oreDispOra, 1) . "h = {$pct}%\n\n";
}
