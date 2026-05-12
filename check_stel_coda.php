<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== Coda fasi STEL/fustella cilindrica schedulate ===\n";
$rows = DB::table('ordine_fasi')
    ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
    ->whereIn('ordine_fasi.fase', ['FUSTSTELG33.44', 'FUSTSTELP25.35'])
    ->whereNull('ordine_fasi.deleted_at')
    ->whereIn('ordine_fasi.stato', ['0', '1', '2'])
    ->orderBy('ordine_fasi.sched_inizio')
    ->select(
        'ordini.commessa',
        'ordini.data_prevista_consegna',
        'ordine_fasi.fase',
        'ordine_fasi.stato',
        'ordine_fasi.priorita',
        'ordine_fasi.priorita_manuale',
        'ordine_fasi.disponibile',
        'ordine_fasi.sched_inizio',
        'ordine_fasi.sched_fine',
        'ordine_fasi.sched_macchina'
    )
    ->get();

echo "Totale fasi cilindriche attive: " . count($rows) . "\n\n";

$manuali = 0;
$nonDisp = 0;
foreach ($rows as $r) {
    $manualeFlag = $r->priorita_manuale ? 'M' : '-';
    $dispFlag = $r->disponibile ? 'D' : 'X';
    if ($r->priorita_manuale) $manuali++;
    if (!$r->disponibile) $nonDisp++;
    echo sprintf("%s | %s %s | stato=%s pr=%s [%s%s] disp=%s | sched=%s → %s | cons=%s\n",
        $r->commessa,
        $r->fase,
        $r->sched_macchina ?? '?',
        $r->stato,
        $r->priorita ?? '-',
        $manualeFlag, $dispFlag,
        $dispFlag,
        $r->sched_inizio ? substr($r->sched_inizio, 0, 16) : '-',
        $r->sched_fine ? substr($r->sched_fine, 0, 16) : '-',
        $r->data_prevista_consegna ?? '-'
    );
}

echo "\nFasi con priorita_manuale=true: $manuali\n";
echo "Fasi non disponibili (predecessori non terminati): $nonDisp\n";
