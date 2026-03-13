<?php
// Uso: php check_prinect.php 0066758-26
// Controlla se la commessa ha dati nella tabella prinect_attivita e nell'API Prinect

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = $argv[1] ?? '0066758-26';
$jobId = ltrim(explode('-', $commessa)[0], '0');

echo "=== Ricerca Prinect per commessa $commessa (jobId=$jobId) ===\n\n";

// 1. Cerca nel DB prinect_attivita
echo "--- DB prinect_attivita ---\n";
$attivita = DB::table('prinect_attivita')
    ->where('commessa_gestionale', $commessa)
    ->get();

if ($attivita->isEmpty()) {
    echo "Nessuna attivita trovata nel DB per $commessa\n";
    // Prova anche senza anno
    $attSenzaAnno = DB::table('prinect_attivita')
        ->where('prinect_job_id', $jobId)
        ->get();
    if ($attSenzaAnno->isNotEmpty()) {
        echo "TROVATE {$attSenzaAnno->count()} attivita con job_id=$jobId ma commessa diversa:\n";
        foreach ($attSenzaAnno as $a) {
            echo "  commessa={$a->commessa_gestionale} ws={$a->workstep_name} good={$a->good_cycles} waste={$a->waste_cycles} start={$a->start_time} end={$a->end_time}\n";
        }
    }
} else {
    echo "Trovate {$attivita->count()} attivita:\n";
    foreach ($attivita as $a) {
        echo "  ws={$a->workstep_name} good={$a->good_cycles} waste={$a->waste_cycles} start={$a->start_time} end={$a->end_time} job_name={$a->prinect_job_name}\n";
    }
}

// 2. Chiedi all'API Prinect
echo "\n--- API Prinect (job $jobId) ---\n";
$prinect = app(\App\Http\Services\PrinectService::class);

$job = $prinect->getJob($jobId);
if (!$job || isset($job['error'])) {
    echo "Job $jobId non trovato nell'API Prinect\n";
} else {
    echo "Job trovato: " . ($job['name'] ?? '?') . "\n";
    echo "  Status: " . ($job['globalStatus'] ?? '?') . "\n";
    echo "  Created: " . ($job['creationDate'] ?? '?') . "\n";

    // Worksteps
    $ws = $prinect->getJobWorksteps($jobId);
    if ($ws && !isset($ws['error'])) {
        $worksteps = $ws['worksteps'] ?? $ws;
        if (is_array($worksteps)) {
            echo "  Worksteps:\n";
            foreach ($worksteps as $w) {
                $nome = $w['name'] ?? '?';
                $stato = $w['status'] ?? '?';
                $start = $w['actualStartDate'] ?? '-';
                $end = $w['actualEndDate'] ?? '-';
                echo "    $nome | status=$stato | start=$start | end=$end\n";
            }
        }
    }
}
