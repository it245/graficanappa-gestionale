<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OrdineFase;
use App\Models\PrinectAttivita;
use Illuminate\Support\Facades\DB;

$fase = OrdineFase::with('ordine')->where('stato','2')
    ->where('fase','like','STAMPAXL106%')
    ->whereHas('ordine', fn($q) => $q->where('commessa','like','%67386%'))
    ->first();

if (!$fase) { echo "Fase non trovata\n"; exit; }

$c = $fase->ordine->commessa;
echo "Commessa fase: [{$c}] len=" . strlen($c) . "\n\n";

// Match esatto
$exact = PrinectAttivita::where('commessa_gestionale', $c)
    ->selectRaw('COUNT(*) n, SUM(good_cycles) buoni, SUM(waste_cycles) scarto')->first();
echo "Match esatto [{$c}]: n={$exact->n} buoni={$exact->buoni} scarto={$exact->scarto}\n";

// LIKE
$like = PrinectAttivita::where('commessa_gestionale', 'like', '%67386%')
    ->selectRaw('COUNT(*) n, SUM(good_cycles) buoni, SUM(waste_cycles) scarto')->first();
echo "LIKE %67386%: n={$like->n} buoni={$like->buoni} scarto={$like->scarto}\n\n";

// Valori distinti commessa_gestionale che contengono 67386
$distinct = PrinectAttivita::where('commessa_gestionale', 'like', '%67386%')
    ->select('commessa_gestionale', DB::raw('COUNT(*) as n'), DB::raw('SUM(good_cycles) as buoni'))
    ->groupBy('commessa_gestionale')->get();
echo "Distinct commessa_gestionale match:\n";
foreach ($distinct as $d) {
    echo "  [{$d->commessa_gestionale}] len=" . strlen($d->commessa_gestionale) . " n={$d->n} buoni={$d->buoni}\n";
}

// Per prinect_job_id 67386
$byJob = PrinectAttivita::where('prinect_job_id', '67386')
    ->selectRaw('COUNT(*) n, SUM(good_cycles) buoni, SUM(waste_cycles) scarto')->first();
echo "\nMatch prinect_job_id=67386: n={$byJob->n} buoni={$byJob->buoni} scarto={$byJob->scarto}\n";
