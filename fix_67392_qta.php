<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Http\Services\PrinectService;
use App\Http\Services\PrinectSyncService;

// Recupera dati Prinect live
$prinect = app(PrinectService::class);
$svc = app(PrinectSyncService::class);

$wsData = $prinect->getJobWorksteps('67392');
$ws = collect($wsData['worksteps'] ?? [])
    ->filter(fn($w) => in_array('ConventionalPrinting', $w['types'] ?? []));

$ref = new ReflectionMethod($svc, 'detectFronteRetro');
$ref->setAccessible(true);
$isFR = $ref->invoke($svc, $ws);

if ($isFR) {
    $buoni = (int) $ws->max(fn($w) => $w['amountProduced'] ?? 0);
    $scarto = (int) $ws->sum(fn($w) => $w['wasteProduced'] ?? 0);
} else {
    $buoni = (int) $ws->sum(fn($w) => $w['amountProduced'] ?? 0);
    $scarto = (int) $ws->sum(fn($w) => $w['wasteProduced'] ?? 0);
}

echo "F/R=$isFR | buoni=$buoni | scarto=$scarto\n";

$n = DB::table('ordine_fasi as orf')
    ->join('ordini as o', 'o.id', '=', 'orf.ordine_id')
    ->where('o.commessa', '0067392-26')
    ->where('orf.fase', 'LIKE', 'STAMPA%')
    ->update([
        'orf.qta_prod' => $buoni,
        'orf.fogli_buoni' => $buoni,
        'orf.fogli_scarto' => $scarto,
    ]);

echo "Update applicato a $n righe.\n";
