<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OrdineFase;
use App\Models\Ordine;
use App\Models\PrinectAttivita;

foreach (['0067379-26', '0067382-26'] as $c) {
    echo "\n=== Commessa $c ===\n";
    $ordini = Ordine::where('commessa', $c)->pluck('id');
    $fasi = OrdineFase::whereIn('ordine_id', $ordini)
        ->where('fase', 'like', '%STAMPA%')
        ->get(['id','ordine_id','fase','stato','qta_prod','fogli_buoni','fogli_scarto','data_inizio','data_fine']);
    foreach ($fasi as $f) {
        echo "  Fase id={$f->id} [{$f->fase}] stato={$f->stato} qta={$f->qta_prod} buoni={$f->fogli_buoni} scarto={$f->fogli_scarto} inizio={$f->data_inizio} fine={$f->data_fine}\n";
    }
    $att = PrinectAttivita::where('commessa_gestionale', $c)
        ->orderBy('start_time')
        ->get(['id','activity_id','activity_name','workstep_name','good_cycles','waste_cycles','start_time','end_time','prinect_job_id','commessa_gestionale']);
    echo "  Prinect attivita (" . $att->count() . "):\n";
    foreach ($att as $a) {
        echo "    {$a->start_time} → {$a->end_time} [{$a->activity_name}] ws={$a->workstep_name} buoni={$a->good_cycles} scarto={$a->waste_cycles} job={$a->prinect_job_id} cg=[{$a->commessa_gestionale}]\n";
    }

    // Anche per prinect_job_id
    $jobId = ltrim(explode('-', $c)[0], '0');
    $byJob = PrinectAttivita::where('prinect_job_id', $jobId)
        ->orderBy('start_time')
        ->get(['id','start_time','end_time','activity_name','good_cycles','waste_cycles','commessa_gestionale']);
    echo "  By prinect_job_id={$jobId} (" . $byJob->count() . "):\n";
    foreach ($byJob as $a) {
        echo "    {$a->start_time} buoni={$a->good_cycles} scarto={$a->waste_cycles} cg=[{$a->commessa_gestionale}]\n";
    }
}
