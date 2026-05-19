<?php
/**
 * Rimuove fasi "fantasma" 67201-26 mai avviate (lamina oro):
 *   - STAMPALAMINAORO
 *   - STAMPACALDOJOH0,1
 * Pre-condizione: stato=1, data_inizio NULL (mai lavorate).
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = '0067201-26';
$fasiDaRimuovere = ['STAMPALAMINAORO', 'STAMPACALDOJOH0,1'];

echo "=== Cleanup fasi fantasma {$commessa} ===\n";

$rows = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'o.id', '=', 'f.ordine_id')
    ->where('o.commessa', $commessa)
    ->whereIn('f.fase', $fasiDaRimuovere)
    ->select('f.id', 'f.fase', 'f.stato', 'f.data_inizio')
    ->get();

if ($rows->isEmpty()) {
    echo "Nessuna fase fantasma trovata.\n";
    exit(0);
}

echo "Trovate " . count($rows) . " fasi:\n";
foreach ($rows as $r) {
    echo "  id={$r->id} | {$r->fase} | stato={$r->stato} | data_inizio=" . ($r->data_inizio ?? 'NULL') . "\n";
}

$ids = $rows->pluck('id')->all();
$deleted = DB::table('ordine_fasi')
    ->whereIn('id', $ids)
    ->whereNull('data_inizio')   // safety: solo mai avviate
    ->whereIn('stato', ['0', '1'])
    ->delete();

echo "\nFasi cancellate: {$deleted}\n";
