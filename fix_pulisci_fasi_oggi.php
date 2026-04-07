<?php
// Pulisce le fasi duplicate create oggi dal bug dedup stampa per-ordine
// Elimina (soft delete) tutte le fasi create oggi su commesse vecchie (data_reg < oggi)
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$oggi = date('Y-m-d');

// Trova tutte le fasi create oggi
$fasi = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'f.ordine_id', '=', 'o.id')
    ->whereDate('f.created_at', $oggi)
    ->whereNull('f.deleted_at')
    ->select('f.id', 'f.fase', 'f.stato', 'o.commessa', 'o.data_registrazione')
    ->get();

echo "Fasi create oggi: {$fasi->count()}\n";

// Filtra: elimina solo quelle su commesse con data_registrazione < oggi
$daEliminare = $fasi->filter(fn($f) => $f->data_registrazione && $f->data_registrazione < $oggi);

echo "Fasi su commesse vecchie: {$daEliminare->count()}\n\n";

// Soft delete
$ids = $daEliminare->pluck('id')->toArray();
if (!empty($ids)) {
    $chunks = array_chunk($ids, 500);
    $totale = 0;
    foreach ($chunks as $chunk) {
        $deleted = DB::table('ordine_fasi')
            ->whereIn('id', $chunk)
            ->update(['deleted_at' => now()]);
        $totale += $deleted;
    }
    echo "Soft deleted: {$totale} fasi\n";
} else {
    echo "Nessuna fase da eliminare\n";
}

// Anche le fasi create oggi alle 08:46 (fix_66335_pi01.php)
$fasiPi01 = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'f.ordine_id', '=', 'o.id')
    ->where('o.commessa', '0066335-26')
    ->where('f.fase', 'PI01')
    ->whereDate('f.created_at', $oggi)
    ->whereNull('f.deleted_at')
    ->where('f.stato', 3)
    ->count();
echo "\nPI01 66335 create oggi (fix precedente): {$fasiPi01} — queste NON vanno toccate\n";
