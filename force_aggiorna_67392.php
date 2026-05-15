<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Services\PrinectSyncService;

$svc = app(PrinectSyncService::class);
// Usa reflection per accedere a metodo protected
$ref = new ReflectionMethod($svc, 'aggiornaFogliCommessa');
$ref->setAccessible(true);
$ref->invoke($svc, '0067392-26');

echo "Aggiornata commessa 0067392-26\n";

// Verifica
use Illuminate\Support\Facades\DB;
$fasi = DB::table('ordine_fasi as orf')
    ->join('ordini as o', 'o.id', '=', 'orf.ordine_id')
    ->where('o.commessa', '0067392-26')
    ->where('orf.fase', 'LIKE', 'STAMPA%')
    ->select('orf.fase', 'orf.qta_prod', 'orf.fogli_buoni', 'orf.fogli_scarto')
    ->get();
foreach ($fasi as $f) {
    echo "  {$f->fase}: qta_prod={$f->qta_prod} buoni={$f->fogli_buoni} scarto={$f->fogli_scarto}\n";
}
