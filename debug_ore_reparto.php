<?php
// Debug ore segnate per piegaincolla e tagliacarte
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$oggi = date('Y-m-d');
echo "Data: {$oggi}\n\n";

foreach (['piegaincolla' => 6, 'tagliacarte' => 8] as $rep => $inizio) {
    $repartoIds = DB::table('reparti')->where('nome', $rep)->pluck('id');
    $inizioTurno = $oggi . ' ' . str_pad($inizio, 2, '0', STR_PAD_LEFT) . ':00:00';
    echo "=== {$rep} (reparto_id: {$repartoIds->implode(',')}, turno dalle {$inizio}) ===\n";

    // Tutti i record fase_operatore di oggi per questo reparto
    $records = DB::table('fase_operatore')
        ->join('ordine_fasi', 'fase_operatore.fase_id', '=', 'ordine_fasi.id')
        ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
        ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
        ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
        ->where(function ($q) use ($oggi) {
            $q->whereDate('fase_operatore.data_inizio', $oggi)
              ->orWhereDate('fase_operatore.data_fine', $oggi)
              ->orWhere(function ($q2) {
                  $q2->whereNull('fase_operatore.data_fine')
                     ->where('ordine_fasi.stato', 2);
              });
        })
        ->select('ordini.commessa', 'ordine_fasi.fase', 'ordine_fasi.stato as fase_stato',
                 'fase_operatore.data_inizio', 'fase_operatore.data_fine',
                 'fase_operatore.secondi_pausa', 'fase_operatore.operatore_id',
                 'fasi_catalogo.reparto_id')
        ->get();

    echo "  Records fase_operatore matchati: {$records->count()}\n";
    foreach ($records as $r) {
        echo "    {$r->commessa} | {$r->fase} | stato:{$r->fase_stato} | op:{$r->operatore_id} | inizio:{$r->data_inizio} | fine:" . ($r->data_fine ?? 'NULL') . " | rep_id:{$r->reparto_id}\n";
    }

    // Query esatta del kiosk
    $secPivot = DB::table('fase_operatore')
        ->join('ordine_fasi', 'fase_operatore.fase_id', '=', 'ordine_fasi.id')
        ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
        ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
        ->where(function ($q) use ($oggi) {
            $q->whereDate('fase_operatore.data_inizio', $oggi)
              ->orWhereDate('fase_operatore.data_fine', $oggi)
              ->orWhere(function ($q2) {
                  $q2->whereNull('fase_operatore.data_fine')
                     ->where('ordine_fasi.stato', 2);
              });
        })
        ->selectRaw("SUM(TIMESTAMPDIFF(SECOND, GREATEST(fase_operatore.data_inizio, ?), COALESCE(fase_operatore.data_fine, NOW())) - COALESCE(fase_operatore.secondi_pausa, 0)) as sec", [$inizioTurno])
        ->value('sec');

    echo "  Pivot sec: " . ($secPivot ?? 'NULL') . " (" . round(max($secPivot ?? 0, 0) / 3600, 2) . "h)\n\n";
}
