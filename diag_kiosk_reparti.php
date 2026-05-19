<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Reparto;
use Carbon\Carbon;

$oggi = Carbon::today()->format('Y-m-d');
$ieri = Carbon::yesterday()->format('Y-m-d');

$reparti = ['fustella piana', 'fustella cilindrica', 'stampa offset'];
$inizioTurnoOra = ['fustella piana' => 6, 'fustella cilindrica' => 8, 'stampa offset' => 6];
$oreDispMap = ['fustella piana' => 16, 'fustella cilindrica' => 8, 'stampa offset' => 16];

foreach ($reparti as $nome) {
    $repartoIds = Reparto::whereIn('nome', [$nome])->pluck('id');
    if ($repartoIds->isEmpty()) { echo "$nome: REPARTO NON TROVATO\n"; continue; }
    $inizioTurno = $oggi . ' 06:00:00';

    $secAperte = DB::table('ordine_fasi')
        ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
        ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
        ->whereNull('ordine_fasi.deleted_at')
        ->where(fn($q) => $q->where('ordine_fasi.esterno', 0)->orWhereNull('ordine_fasi.esterno'))
        ->where('ordine_fasi.stato', 2)
        ->selectRaw("SUM(TIMESTAMPDIFF(SECOND, GREATEST(COALESCE(ordine_fasi.data_inizio, ?), ?), NOW())) as sec", [$inizioTurno, $inizioTurno])
        ->value('sec') ?? 0;

    $secChiuseRecenti = DB::table('ordine_fasi')
        ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
        ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
        ->whereNull('ordine_fasi.deleted_at')
        ->where(fn($q) => $q->where('ordine_fasi.esterno', 0)->orWhereNull('ordine_fasi.esterno'))
        ->where('ordine_fasi.stato', 3)
        ->whereDate('ordine_fasi.data_fine', $oggi)
        ->where('ordine_fasi.data_inizio', '>=', $ieri . ' 00:00:00')
        ->selectRaw("SUM(TIMESTAMPDIFF(SECOND, GREATEST(ordine_fasi.data_inizio, ?), ordine_fasi.data_fine)) as sec", [$inizioTurno])
        ->value('sec') ?? 0;

    $countTerminate = DB::table('ordine_fasi')
        ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
        ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
        ->where('ordine_fasi.stato', 3)
        ->whereDate('ordine_fasi.data_fine', $oggi)
        ->count();

    $countAperte = DB::table('ordine_fasi')
        ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
        ->whereIn('fasi_catalogo.reparto_id', $repartoIds)
        ->where('ordine_fasi.stato', 2)
        ->count();

    $h_aperte = round($secAperte / 3600, 2);
    $h_chiuse = round($secChiuseRecenti / 3600, 2);
    echo "$nome (id={$repartoIds->implode(',')}): aperte=$countAperte/{$h_aperte}h | chiuse oggi=$countTerminate/{$h_chiuse}h\n";
}
