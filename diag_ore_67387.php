<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = '0067387-26';

echo "=== Fasi 67387 con tempi ===\n";
$fasi = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'o.id', 'f.ordine_id')
    ->leftJoin('fasi_catalogo as fc', 'fc.id', 'f.fase_catalogo_id')
    ->leftJoin('reparti as r', 'r.id', 'fc.reparto_id')
    ->where('o.commessa', $commessa)
    ->whereNull('f.deleted_at')
    ->select('f.id', 'f.fase', 'f.stato', 'f.data_inizio', 'f.data_fine',
             'f.tempo_avviamento_sec', 'f.tempo_esecuzione_sec',
             'r.nome as reparto')
    ->get();

foreach ($fasi as $f) {
    $secProprio = (int)($f->tempo_avviamento_sec ?? 0) + (int)($f->tempo_esecuzione_sec ?? 0);
    $oreH = round($secProprio / 3600, 2);
    echo "  fase_id={$f->id} | {$f->fase} | reparto={$f->reparto} | stato={$f->stato} | data_inizio={$f->data_inizio} | data_fine=" . ($f->data_fine ?? 'NULL') . " | secProprio={$secProprio}s ({$oreH}h)\n";

    // Pivot operatore
    $pivots = DB::table('fase_operatore')->where('fase_id', $f->id)->get();
    foreach ($pivots as $p) {
        $secPivot = 0;
        if ($p->data_inizio && $p->data_fine) {
            $secPivot = strtotime($p->data_fine) - strtotime($p->data_inizio) - (int)($p->secondi_pausa ?? 0);
        } elseif ($p->data_inizio) {
            $secPivot = time() - strtotime($p->data_inizio);
        }
        $oreP = round($secPivot / 3600, 2);
        $aperta = $p->data_fine ? '' : ' ⚠️ APERTA';
        echo "    pivot: op_id={$p->operatore_id} | inizio={$p->data_inizio} | fine=" . ($p->data_fine ?? 'NULL') . " | pausa_sec=" . ($p->secondi_pausa ?? 0) . " | sec={$secPivot} ({$oreP}h){$aperta}\n";
    }
}
