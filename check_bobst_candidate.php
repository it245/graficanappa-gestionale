<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$soglia = '2026-05-14 23:59:59'; // fine gio 14/05
$ordine = require __DIR__ . '/config/fasi_priorita.php';

// Config BOBST
$configMap = [
    'RILIEVI'  => ['FUSTBOBSTRILIEVI'],
    'FUSTELLE' => ['FUSTBOBST75X106', 'FUSTIML75X106'],
];

echo "\n=== Candidate BOBST per gap 12-14/05 ===\n";
echo "Criterio: stato 0/1, sched_macchina=BOBST, predecessori sched_fine <= $soglia\n\n";

$fasi = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->where('ordine_fasi.sched_macchina', 'BOBST')
    ->whereNull('ordine_fasi.deleted_at')
    ->whereIn('ordine_fasi.stato', ['0', '1'])
    ->select(
        'ordine_fasi.id',
        'ordine_fasi.ordine_id',
        'ordini.commessa',
        'ordini.cliente_nome',
        'ordini.data_prevista_consegna as cons',
        'ordine_fasi.fase',
        'ordine_fasi.stato',
        'ordine_fasi.sched_inizio',
        'ordine_fasi.sched_fine'
    )
    ->orderBy('ordine_fasi.sched_inizio')
    ->get();

$perConfig = ['RILIEVI' => [], 'FUSTELLE' => [], 'ALTRO' => []];

foreach ($fasi as $f) {
    // Trova config
    $cfg = 'ALTRO';
    foreach ($configMap as $cName => $cFasi) {
        if (in_array($f->fase, $cFasi)) { $cfg = $cName; break; }
    }

    // Predecessori = altre fasi commessa con seq inferiore
    $seq = $ordine[$f->fase] ?? 999;
    $pred = DB::table('ordine_fasi')
        ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
        ->where('ordini.commessa', $f->commessa)
        ->whereNull('ordine_fasi.deleted_at')
        ->where('ordine_fasi.id', '!=', $f->id)
        ->get();

    $maxFinePred = null;
    $bloccato = false;
    foreach ($pred as $p) {
        $pSeq = $ordine[$p->fase] ?? 999;
        if ($pSeq >= $seq) continue;
        if ((int) $p->stato >= 3) continue; // terminata
        if (!$p->sched_fine) { $bloccato = true; break; }
        if (!$maxFinePred || $p->sched_fine > $maxFinePred) $maxFinePred = $p->sched_fine;
    }
    if ($bloccato) continue;
    $dispDa = $maxFinePred ?? '-';
    if ($maxFinePred && $maxFinePred > $soglia) continue;

    $perConfig[$cfg][] = [
        'commessa' => $f->commessa,
        'fase'     => $f->fase,
        'stato'    => $f->stato,
        'cliente'  => substr($f->cliente_nome ?? '-', 0, 25),
        'cons'     => $f->cons ?? '-',
        'dispDa'   => $dispDa,
        'sched'    => $f->sched_inizio,
    ];
}

foreach (['RILIEVI', 'FUSTELLE', 'ALTRO'] as $cfg) {
    $rows = $perConfig[$cfg];
    echo "── Config $cfg: " . count($rows) . " candidate ──\n";
    if (empty($rows)) { echo "  (nessuna)\n\n"; continue; }
    echo sprintf("%-13s %-17s %-2s %-26s %-11s %-16s %-16s\n",
        'commessa', 'fase', 'st', 'cliente', 'consegna', 'dispDa', 'schedulata');
    foreach ($rows as $r) {
        echo sprintf("%-13s %-17s %-2s %-26s %-11s %-16s %-16s\n",
            $r['commessa'],
            $r['fase'],
            $r['stato'],
            $r['cliente'],
            $r['cons'],
            substr($r['dispDa'], 0, 16),
            substr($r['sched'] ?? '-', 0, 16)
        );
    }
    echo "\n";
}

// Capacita' BOBST 12-14/05
$ore12 = 16; // 6-22
$ore13 = 16;
$ore14 = 16;
$totOreLib = $ore12 + $ore13 + $ore14;
echo "Capacita' BOBST 12-14/05 turno 6-22: " . $totOreLib . "h disponibili\n";
echo "Considera 1h penalty per cambio config\n";
