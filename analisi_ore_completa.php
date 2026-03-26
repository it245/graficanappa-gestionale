<?php
// Analisi completa ore segnate: tutte le fasi stato 2 e 3 (chiuse oggi) per reparto
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$oggi = date('Y-m-d');
$oraCorrente = (float)date('H') + (float)date('i') / 60;
echo "=== ANALISI ORE COMPLETE — {$oggi} ore " . date('H:i') . " ===\n\n";

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

    $oreDispOra = max(min($oraCorrente - $ru['inizio'], $ru['ore_disp']), 0.5);
    $inizioTurno = $oggi . ' ' . str_pad($ru['inizio'], 2, '0', STR_PAD_LEFT) . ':00:00';

    echo "=== {$ru['nome']} (turno dalle {$ru['inizio']}, ore disponibili finora: " . round($oreDispOra, 1) . "h) ===\n";

    // FASI STATO 2 (in corso)
    $aperte = DB::table('ordine_fasi as f')
        ->join('ordini as o', 'f.ordine_id', '=', 'o.id')
        ->join('fasi_catalogo as fc', 'f.fase_catalogo_id', '=', 'fc.id')
        ->whereIn('fc.reparto_id', $repartoIds)
        ->where('f.stato', 2)
        ->select('f.id', 'f.fase', 'f.data_inizio', 'o.commessa')
        ->get();

    // FASI STATO 3 chiuse oggi
    $chiuse = DB::table('ordine_fasi as f')
        ->join('ordini as o', 'f.ordine_id', '=', 'o.id')
        ->join('fasi_catalogo as fc', 'f.fase_catalogo_id', '=', 'fc.id')
        ->whereIn('fc.reparto_id', $repartoIds)
        ->where('f.stato', 3)
        ->whereDate('f.data_fine', $oggi)
        ->select('f.id', 'f.fase', 'f.data_inizio', 'f.data_fine', 'o.commessa')
        ->get();

    $totSec = 0;
    $dettaglio = [];

    // Per ogni fase (aperta o chiusa oggi), cerca pivot
    $tutteFasi = $aperte->map(fn($f) => (object)array_merge((array)$f, ['tipo' => 'APERTA']))
        ->merge($chiuse->map(fn($f) => (object)array_merge((array)$f, ['tipo' => 'CHIUSA'])));

    foreach ($tutteFasi as $f) {
        $pivots = DB::table('fase_operatore')->where('fase_id', $f->id)->get();

        $secFase = 0;
        $fonte = '';

        if ($pivots->isNotEmpty()) {
            foreach ($pivots as $p) {
                $pInizio = $p->data_inizio;
                $pFine = $p->data_fine;
                $pausa = $p->secondi_pausa ?? 0;

                // Determina se questo pivot è rilevante per oggi
                $rilevante = false;
                if ($pInizio && strpos($pInizio, $oggi) === 0) $rilevante = true; // avviato oggi
                if ($pFine && strpos($pFine, $oggi) === 0) $rilevante = true; // finito oggi
                if (!$pFine && $f->tipo === 'APERTA') $rilevante = true; // in corso

                if (!$rilevante) continue;

                $start = max(strtotime($pInizio), strtotime($inizioTurno));
                $end = $pFine ? strtotime($pFine) : time();
                $sec = max($end - $start - $pausa, 0);
                $secFase += $sec;
            }
            $fonte = 'PIVOT';
        } else {
            // Nessun pivot — fallback su ordine_fasi
            if ($f->tipo === 'APERTA') {
                $start = max(strtotime($f->data_inizio ?: $inizioTurno), strtotime($inizioTurno));
                $sec = max(time() - $start, 0);
                $secFase = $sec;
                $fonte = 'FALLBACK';
            } elseif ($f->tipo === 'CHIUSA' && $f->data_fine) {
                $start = strtotime($inizioTurno);
                $end = strtotime($f->data_fine);
                $secFase = max($end - $start, 0);
                $fonte = 'FALLBACK';
            }
        }

        if ($secFase > 0) {
            $ore = round($secFase / 3600, 2);
            $totSec += $secFase;
            $dettaglio[] = "    {$f->tipo} {$f->commessa} | {$f->fase} | {$ore}h | {$fonte}";
        }
    }

    $totOre = round($totSec / 3600, 2);
    $pct = $oreDispOra > 0 ? min(round(($totOre / $oreDispOra) * 100), 100) : 0;

    echo "  Aperte: {$aperte->count()} | Chiuse oggi: {$chiuse->count()}\n";
    foreach ($dettaglio as $d) echo "{$d}\n";
    echo "  TOTALE: {$totOre}h / {$oreDispOra}h = {$pct}%\n\n";
}
