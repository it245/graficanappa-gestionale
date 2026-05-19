<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Reparto;
use Carbon\Carbon;

$oggi = Carbon::today()->format('Y-m-d');
$ieri = Carbon::yesterday()->format('Y-m-d');
$oraCorrente = (float) now()->format('H') + (float) now()->format('i') / 60;

$config = [
    ['nome' => 'BOBST', 'reparti' => ['fustella piana'], 'ore_disp' => 16, 'inizio' => 6],
    ['nome' => 'Legatoria', 'reparti' => ['legatoria'], 'ore_disp' => 14, 'inizio' => 6],
    ['nome' => 'Fustella Cil', 'reparti' => ['fustella cilindrica'], 'ore_disp' => 8, 'inizio' => 8],
    ['nome' => 'Piegaincolla', 'reparti' => ['piegaincolla'], 'ore_disp' => 14, 'inizio' => 6],
];

foreach ($config as $c) {
    $repartoIds = Reparto::whereIn('nome', $c['reparti'])->pluck('id');
    $inizioTurno = $oggi . ' ' . str_pad($c['inizio'], 2, '0', STR_PAD_LEFT) . ':00:00';

    // A
    $secAperte = (int) DB::table('ordine_fasi')
        ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
        ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
        ->whereNull('ordine_fasi.deleted_at')
        ->where(fn($q) => $q->where('ordine_fasi.esterno', 0)->orWhereNull('ordine_fasi.esterno'))
        ->where(fn($q) => $q->whereNull('ordine_fasi.note')->orWhere('ordine_fasi.note', 'NOT LIKE', '%Inviato a:%'))
        ->where('ordine_fasi.stato', 2)
        ->selectRaw("SUM(TIMESTAMPDIFF(SECOND, GREATEST(COALESCE(ordine_fasi.data_inizio, ?), ?), NOW())) as sec", [$inizioTurno, $inizioTurno])
        ->value('sec');

    // B
    $secChiuseRecenti = (int) DB::table('ordine_fasi')
        ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
        ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
        ->whereNull('ordine_fasi.deleted_at')
        ->where(fn($q) => $q->where('ordine_fasi.esterno', 0)->orWhereNull('ordine_fasi.esterno'))
        ->where(fn($q) => $q->whereNull('ordine_fasi.note')->orWhere('ordine_fasi.note', 'NOT LIKE', '%Inviato a:%'))
        ->where('ordine_fasi.stato', 3)
        ->whereDate('ordine_fasi.data_fine', $oggi)
        ->where('ordine_fasi.data_inizio', '>=', $ieri . ' 00:00:00')
        ->selectRaw("SUM(TIMESTAMPDIFF(SECOND, GREATEST(ordine_fasi.data_inizio, ?), ordine_fasi.data_fine)) as sec", [$inizioTurno])
        ->value('sec');

    // C
    $secChiuseVecchie = (int) DB::table('ordine_fasi')
        ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
        ->join('fase_operatore', 'fase_operatore.fase_id', '=', 'ordine_fasi.id')
        ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
        ->whereNull('ordine_fasi.deleted_at')
        ->where(fn($q) => $q->where('ordine_fasi.esterno', 0)->orWhereNull('ordine_fasi.esterno'))
        ->where(fn($q) => $q->whereNull('ordine_fasi.note')->orWhere('ordine_fasi.note', 'NOT LIKE', '%Inviato a:%'))
        ->where('ordine_fasi.stato', 3)
        ->whereDate('ordine_fasi.data_fine', $oggi)
        ->where('ordine_fasi.data_inizio', '<', $ieri . ' 00:00:00')
        ->where(function ($q) use ($oggi) {
            $q->whereDate('fase_operatore.data_inizio', $oggi)
              ->orWhereDate('fase_operatore.data_fine', $oggi);
        })
        ->selectRaw("SUM(TIMESTAMPDIFF(SECOND, GREATEST(fase_operatore.data_inizio, ?), COALESCE(fase_operatore.data_fine, ordine_fasi.data_fine))) as sec", [$inizioTurno])
        ->value('sec');

    // D
    $secPauseOggi = (int) DB::table('pausa_operatores')
        ->join('ordine_fasi', function ($j) {
            $j->on('ordine_fasi.ordine_id', '=', 'pausa_operatores.ordine_id')
              ->on('ordine_fasi.fase', '=', 'pausa_operatores.fase');
        })
        ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
        ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
        ->whereNull('ordine_fasi.deleted_at')
        ->where('pausa_operatores.data_ora', '>=', $inizioTurno)
        ->selectRaw("SUM(TIMESTAMPDIFF(SECOND, GREATEST(pausa_operatores.data_ora, ?), COALESCE(pausa_operatores.fine, NOW()))) as sec", [$inizioTurno])
        ->value('sec');

    $secOggi = max((max($secAperte, 0) + max($secChiuseRecenti, 0) + max($secChiuseVecchie, 0)) - max($secPauseOggi, 0), 0);
    $oreUsate = round($secOggi / 3600, 2);
    $oreDispOra = max(min($oraCorrente - $c['inizio'], $c['ore_disp']), 0.5);
    $pct = $oreDispOra > 0 ? min(round(($oreUsate / $oreDispOra) * 100), 100) : 0;

    $hA = round(max($secAperte,0)/3600, 2);
    $hB = round(max($secChiuseRecenti,0)/3600, 2);
    $hC = round(max($secChiuseVecchie,0)/3600, 2);
    $hD = round(max($secPauseOggi,0)/3600, 2);

    echo "{$c['nome']}: A={$hA}h B={$hB}h C={$hC}h D(pause)={$hD}h → usate={$oreUsate}h / disp={$oreDispOra}h → {$pct}%\n";
}
