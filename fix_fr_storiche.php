<?php
/**
 * Ricalcola fogli_buoni/qta_prod per tutte le fasi STAMPA stato>=2
 * applicando logica F/R retroattiva. Tutte le commesse, no filtro data.
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Http\Services\PrinectService;
use App\Http\Services\PrinectSyncService;

$prinect = app(PrinectService::class);
$svc = app(PrinectSyncService::class);
$refCalc = new ReflectionMethod($svc, 'calcolaBuoniFronteRetro');
$refCalc->setAccessible(true);

// Commesse con STAMPA XL ultimi 60 giorni
$commesse = DB::table('ordine_fasi as orf')
    ->join('ordini as o', 'o.id', '=', 'orf.ordine_id')
    ->where(function($q) {
        $q->where('orf.fase', 'LIKE', 'STAMPAXL106%')
          ->orWhere('orf.fase', 'STAMPA')
          ->orWhere('orf.fase', 'LIKE', 'STAMPA XL%');
    })
    ->whereIn('orf.stato', ['2','3','4'])
    ->where('orf.qta_prod', '>', 0)
    ->select('o.commessa')
    ->distinct()
    ->pluck('commessa');

echo "Commesse da analizzare: " . count($commesse) . "\n\n";

$fixCount = 0;
$skipCount = 0;
$processed = 0;
foreach ($commesse as $commessa) {
    $processed++;
    if ($processed % 50 === 0) {
        echo "[$processed/" . count($commesse) . "] fixate finora: $fixCount\n";
    }
    $jobId = ltrim(explode('-', $commessa)[0] ?? '', '0');
    if (!$jobId || !is_numeric($jobId)) continue;

    try {
        $wsData = $prinect->getJobWorksteps($jobId);
    } catch (\Exception $e) {
        $skipCount++;
        continue;
    }

    $ws = collect($wsData['worksteps'] ?? [])
        ->filter(fn($w) => in_array('ConventionalPrinting', $w['types'] ?? []));

    if ($ws->count() < 2) continue;
    $buoni = $refCalc->invoke($svc, $ws);
    if ($buoni === null) continue;  // No F/R rilevato

    // Scarto sempre SUM (eventi separati fronte+retro)
    $scarto = (int) $ws->sum(fn($w) => $w['wasteProduced'] ?? 0);

    $r = DB::table('ordine_fasi as orf')
        ->join('ordini as o', 'o.id', '=', 'orf.ordine_id')
        ->where('o.commessa', $commessa)
        ->where(function($q) {
            $q->where('orf.fase', 'LIKE', 'STAMPAXL106%')
              ->orWhere('orf.fase', 'STAMPA')
              ->orWhere('orf.fase', 'LIKE', 'STAMPA XL%');
        })
        ->select('orf.id', 'orf.fogli_buoni')
        ->get();

    foreach ($r as $row) {
        if ($row->fogli_buoni == $buoni) continue;
        DB::table('ordine_fasi')->where('id', $row->id)->update([
            'qta_prod' => $buoni,
            'fogli_buoni' => $buoni,
            'fogli_scarto' => $scarto,
        ]);
        echo "  $commessa fase_id={$row->id}: {$row->fogli_buoni} → $buoni (scarto $scarto)\n";
        $fixCount++;
    }
}

echo "\nFasi aggiornate: $fixCount | Skip (errori API): $skipCount\n";
