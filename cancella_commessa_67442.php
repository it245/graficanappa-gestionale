<?php
/**
 * Cancella commessa 0067442-26 da MES (def2.0).
 * Backup JSON prima della cancellazione in storage/app/backup_67442.json.
 * Tabelle toccate:
 *   - fase_operatore (timbrature operatori sulle fasi)
 *   - ordine_fasi (fasi degli ordini della commessa)
 *   - commessa_dati_costi (override costi)
 *   - commessa_altri_costi (costi manuali)
 *   - ordini (ordini della commessa)
 * Esegui con --confirm per cancellare. Senza flag = solo preview.
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = '0067442-26';
$confirm = in_array('--confirm', $argv);

echo "=== Preview commessa {$commessa} ===\n";

$ordini = DB::table('ordini')->where('commessa', $commessa)->get();
if ($ordini->isEmpty()) {
    echo "Commessa non trovata.\n";
    exit(0);
}

$ordineIds = $ordini->pluck('id')->all();
$fasi = DB::table('ordine_fasi')->whereIn('ordine_id', $ordineIds)->get();
$faseIds = $fasi->pluck('id')->all();
$pivot = DB::table('fase_operatore')->whereIn('fase_id', $faseIds)->get();
$override = DB::table('commessa_dati_costi')->where('commessa', $commessa)->get();
$altri = DB::table('commessa_altri_costi')->where('commessa', $commessa)->get();

echo "  Ordini: " . count($ordini) . "\n";
foreach ($ordini as $o) {
    echo "    id={$o->id} | " . substr($o->descrizione ?? '', 0, 50) . "\n";
}
echo "  Fasi: " . count($fasi) . "\n";
foreach ($fasi as $f) {
    echo "    id={$f->id} | {$f->fase} | stato={$f->stato}\n";
}
echo "  Timbrature operatore: " . count($pivot) . "\n";
echo "  Override costi: " . count($override) . "\n";
echo "  Altri costi: " . count($altri) . "\n";

if (!$confirm) {
    echo "\n⚠️  Solo PREVIEW. Esegui con --confirm per cancellare.\n";
    exit(0);
}

// Backup
$backup = compact('ordini', 'fasi', 'pivot', 'override', 'altri');
$backupPath = storage_path('app/backup_67442_' . date('YmdHis') . '.json');
file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\nBackup salvato: {$backupPath}\n";

DB::transaction(function () use ($faseIds, $ordineIds, $commessa) {
    $a = DB::table('fase_operatore')->whereIn('fase_id', $faseIds)->delete();
    $b = DB::table('ordine_fasi')->whereIn('id', $faseIds)->delete();
    $c = DB::table('commessa_dati_costi')->where('commessa', $commessa)->delete();
    $d = DB::table('commessa_altri_costi')->where('commessa', $commessa)->delete();
    $e = DB::table('ordini')->whereIn('id', $ordineIds)->delete();
    echo "Cancellati: fase_operatore={$a}, ordine_fasi={$b}, commessa_dati_costi={$c}, commessa_altri_costi={$d}, ordini={$e}\n";
});

echo "\n✅ Commessa {$commessa} eliminata.\n";
