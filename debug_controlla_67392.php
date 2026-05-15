<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Services\PrinectService;
use App\Http\Services\PrinectSyncService;
use Illuminate\Support\Facades\DB;

$prinect = app(PrinectService::class);
$svc = app(PrinectSyncService::class);

echo "=== File codice PrinectSyncService ===\n";
$ref = new ReflectionClass($svc);
echo "Path: " . $ref->getFileName() . "\n";
echo "detectFronteRetro: " . ($ref->hasMethod('detectFronteRetro') ? 'YES' : 'NO') . "\n";
echo "calcolaBuoniFronteRetro: " . ($ref->hasMethod('calcolaBuoniFronteRetro') ? 'YES' : 'NO') . "\n\n";

$wsData = $prinect->getJobWorksteps('67392');
$ws = collect($wsData['worksteps'] ?? [])
    ->filter(fn($w) => in_array('ConventionalPrinting', $w['types'] ?? []));

echo "=== Worksteps ConventionalPrinting per 67392 ===\n";
foreach ($ws as $w) {
    echo "  name='{$w['name']}' status={$w['status']} prodotti={$w['amountProduced']} scarto={$w['wasteProduced']}\n";
}

if ($ref->hasMethod('detectFronteRetro')) {
    $m1 = $ref->getMethod('detectFronteRetro');
    $m1->setAccessible(true);
    echo "\ndetectFronteRetro = " . ($m1->invoke($svc, $ws) ? 'TRUE' : 'FALSE') . "\n";
}

if ($ref->hasMethod('calcolaBuoniFronteRetro')) {
    $m2 = $ref->getMethod('calcolaBuoniFronteRetro');
    $m2->setAccessible(true);
    echo "calcolaBuoniFronteRetro = " . ($m2->invoke($svc, $ws) ?? 'NULL') . "\n";
}

echo "\n=== Calcoli raw ===\n";
echo "MAX amountProduced: " . $ws->max(fn($w) => $w['amountProduced'] ?? 0) . "\n";
echo "SUM amountProduced: " . $ws->sum(fn($w) => $w['amountProduced'] ?? 0) . "\n";
echo "SUM wasteProduced: " . $ws->sum(fn($w) => $w['wasteProduced'] ?? 0) . "\n";

echo "\n=== DB attuale 67392 STAMPA ===\n";
$rows = DB::table('ordine_fasi as orf')
    ->join('ordini as o', 'o.id', '=', 'orf.ordine_id')
    ->where('o.commessa', 'LIKE', '67392%')
    ->where(function($q) {
        $q->where('orf.fase', 'LIKE', 'STAMPA%');
    })
    ->select('orf.id', 'o.commessa', 'orf.fase', 'orf.stato', 'orf.qta_prod', 'orf.fogli_buoni', 'orf.fogli_scarto', 'orf.updated_at')
    ->get();
foreach ($rows as $r) {
    echo "  id={$r->id} commessa={$r->commessa} fase={$r->fase} stato={$r->stato} qta_prod={$r->qta_prod} fogli_buoni={$r->fogli_buoni} fogli_scarto={$r->fogli_scarto} upd={$r->updated_at}\n";
}

echo "\n=== Opcache CLI ===\n";
echo "opcache.enable_cli: " . ini_get('opcache.enable_cli') . "\n";
echo "opcache.validate_timestamps: " . ini_get('opcache.validate_timestamps') . "\n";
if (function_exists('opcache_get_status')) {
    $st = opcache_get_status(false);
    echo "opcache active: " . ($st ? 'YES' : 'NO') . "\n";
}
