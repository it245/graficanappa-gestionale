<?php
// Mostra per ogni reparto: fasi a stato 2 (aperte) e fasi chiuse oggi (stato 3 con data_fine oggi)
// + verifica se hanno record in fase_operatore
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$oggi = date('Y-m-d');
echo "=== ORE SEGNATE OGGI ({$oggi}) — ANALISI PER REPARTO ===\n\n";

$reparti = [
    'XL 106 (16h)' => ['stampa offset'],
    'BOBST' => ['fustella piana'],
    'JOH Caldo' => ['stampa a caldo'],
    'Plastificatrice' => ['plastificazione'],
    'Piegaincolla' => ['piegaincolla'],
    'Finestratrice' => ['finestratura'],
    'Fustella Cilindrica' => ['fustella cilindrica'],
    'Canon V900' => ['digitale', 'finitura digitale'],
    'Tagliacarte' => ['tagliacarte'],
    'Legatoria' => ['legatoria'],
];

foreach ($reparti as $nome => $nomiRep) {
    $repartoIds = DB::table('reparti')->whereIn('nome', $nomiRep)->pluck('id');
    if ($repartoIds->isEmpty()) { echo "--- {$nome}: reparto non trovato\n\n"; continue; }

    echo "--- {$nome} ---\n";

    // Fasi a stato 2 (aperte ora)
    $aperte = DB::table('ordine_fasi as f')
        ->join('ordini as o', 'f.ordine_id', '=', 'o.id')
        ->join('fasi_catalogo as fc', 'f.fase_catalogo_id', '=', 'fc.id')
        ->whereIn('fc.reparto_id', $repartoIds)
        ->where('f.stato', 2)
        ->select('f.id', 'f.fase', 'f.data_inizio', 'o.commessa', 'o.cliente_nome')
        ->get();

    echo "  APERTE (stato 2): {$aperte->count()}\n";
    foreach ($aperte as $a) {
        // Controlla fase_operatore
        $pivot = DB::table('fase_operatore')
            ->where('fase_id', $a->id)
            ->select('data_inizio', 'data_fine', 'operatore_id')
            ->get();

        $pivotInfo = $pivot->isEmpty() ? '*** NESSUN RECORD fase_operatore ***' : $pivot->map(fn($p) => "op:{$p->operatore_id} inizio:{$p->data_inizio} fine:" . ($p->data_fine ?? 'NULL'))->implode(' | ');
        echo "    {$a->commessa} | {$a->fase} | inizio:{$a->data_inizio} | {$a->cliente_nome}\n";
        echo "      Pivot: {$pivotInfo}\n";
    }

    // Fasi chiuse oggi (stato 3, data_fine oggi)
    $chiuse = DB::table('ordine_fasi as f')
        ->join('ordini as o', 'f.ordine_id', '=', 'o.id')
        ->join('fasi_catalogo as fc', 'f.fase_catalogo_id', '=', 'fc.id')
        ->whereIn('fc.reparto_id', $repartoIds)
        ->where('f.stato', 3)
        ->whereDate('f.data_fine', $oggi)
        ->select('f.id', 'f.fase', 'f.data_inizio', 'f.data_fine', 'o.commessa', 'o.cliente_nome')
        ->get();

    echo "  CHIUSE OGGI (stato 3, data_fine oggi): {$chiuse->count()}\n";
    foreach ($chiuse as $c) {
        $pivot = DB::table('fase_operatore')
            ->where('fase_id', $c->id)
            ->select('data_inizio', 'data_fine', 'operatore_id')
            ->get();

        $pivotInfo = $pivot->isEmpty() ? '*** NESSUN RECORD fase_operatore ***' : $pivot->map(fn($p) => "op:{$p->operatore_id} inizio:{$p->data_inizio} fine:" . ($p->data_fine ?? 'NULL'))->implode(' | ');
        echo "    {$c->commessa} | {$c->fase} | inizio:{$c->data_inizio} | fine:{$c->data_fine}\n";
        echo "      Pivot: {$pivotInfo}\n";
    }

    // Ore segnate nel pivot per oggi
    $inizioOggi = $oggi . ' 00:00:00';
    $secPivot = DB::table('fase_operatore')
        ->join('ordine_fasi', 'fase_operatore.fase_id', '=', 'ordine_fasi.id')
        ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
        ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
        ->where(function ($q) use ($oggi) {
            $q->whereDate('fase_operatore.data_inizio', $oggi)
              ->orWhereDate('fase_operatore.data_fine', $oggi);
        })
        ->selectRaw("SUM(TIMESTAMPDIFF(SECOND, GREATEST(fase_operatore.data_inizio, ?), COALESCE(fase_operatore.data_fine, NOW())) - COALESCE(fase_operatore.secondi_pausa, 0)) as sec", [$inizioOggi])
        ->value('sec');

    $ore = round(max($secPivot ?? 0, 0) / 3600, 2);
    echo "  ORE PIVOT OGGI: {$ore}h (sec: {$secPivot})\n\n";
}
