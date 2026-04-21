<?php
/**
 * Diagnosi fasi reparto digitale: conteggi per stato, ultime terminate, sync Fiery.
 * Uso: php check_fasi_digitale.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use Illuminate\Support\Facades\DB;

echo "=== Conteggi fasi reparto 'digitale' per stato ===\n";
$rows = DB::table('ordine_fasi as of')
    ->join('fasi_catalogo as fc', 'of.fase_catalogo_id', 'fc.id')
    ->join('reparti as r', 'fc.reparto_id', 'r.id')
    ->where('r.nome', 'digitale')
    ->select('of.stato', DB::raw('COUNT(*) as n'))
    ->groupBy('of.stato')
    ->orderBy('of.stato')
    ->get();

foreach ($rows as $r) {
    echo sprintf("  stato=%s : %d\n", $r->stato, $r->n);
}

echo "\n=== Ultime 15 fasi terminate nel reparto digitale (ordine per data_fine desc) ===\n";
$terminate = OrdineFase::with(['ordine:id,commessa,descrizione', 'faseCatalogo.reparto'])
    ->whereHas('faseCatalogo.reparto', fn($q) => $q->where('nome', 'digitale'))
    ->where('stato', 3)
    ->whereNotNull('data_fine')
    ->orderByDesc('data_fine')
    ->limit(15)
    ->get();

foreach ($terminate as $f) {
    printf("  [%d] commessa=%s fase=%s data_fine=%s qta_prod=%s\n",
        $f->id, $f->ordine->commessa ?? '-', $f->fase, $f->data_fine, $f->qta_prod);
}

echo "\n=== Fasi digitale create ultime 48h ===\n";
$recenti = OrdineFase::with(['ordine:id,commessa', 'faseCatalogo.reparto'])
    ->whereHas('faseCatalogo.reparto', fn($q) => $q->where('nome', 'digitale'))
    ->where('created_at', '>=', now()->subHours(48))
    ->orderByDesc('created_at')
    ->get();

if ($recenti->isEmpty()) {
    echo "  NESSUNA fase digitale creata nelle ultime 48h\n";
} else {
    foreach ($recenti as $f) {
        printf("  [%d] %s fase=%s stato=%s creata=%s\n",
            $f->id, $f->ordine->commessa ?? '-', $f->fase, $f->stato, $f->created_at);
    }
}

echo "\n=== Fasi digitale terminate ultime 48h ===\n";
$autoTerm = OrdineFase::with(['ordine:id,commessa'])
    ->whereHas('faseCatalogo.reparto', fn($q) => $q->where('nome', 'digitale'))
    ->where('stato', 3)
    ->where('updated_at', '>=', now()->subHours(48))
    ->orderByDesc('updated_at')
    ->get();

if ($autoTerm->isEmpty()) {
    echo "  Nessuna fase digitale terminata recente\n";
} else {
    foreach ($autoTerm as $f) {
        printf("  [%d] %s fase=%s updated=%s data_fine=%s qta_prod=%s\n",
            $f->id, $f->ordine->commessa ?? '-', $f->fase, $f->updated_at, $f->data_fine, $f->qta_prod);
    }
}

echo "\n=== Fasi STAMPAINDIGO/STAMPAINDIGOBN totali globali per stato ===\n";
$indigo = DB::table('ordine_fasi')
    ->whereIn('fase', ['STAMPAINDIGO', 'STAMPAINDIGOBN'])
    ->select('stato', 'fase', DB::raw('COUNT(*) as n'))
    ->groupBy('stato', 'fase')
    ->orderBy('fase')
    ->orderBy('stato')
    ->get();

foreach ($indigo as $r) {
    printf("  %s stato=%s : %d\n", $r->fase, $r->stato, $r->n);
}

echo "\n=== FaseCatalogo reparto digitale ===\n";
$cat = FasiCatalogo::with('reparto')
    ->whereHas('reparto', fn($q) => $q->where('nome', 'digitale'))
    ->get();

foreach ($cat as $c) {
    printf("  id=%d nome=%s reparto=%s\n", $c->id, $c->nome, $c->reparto->nome ?? '-');
}
