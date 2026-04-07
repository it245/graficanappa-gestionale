<?php
// Fasi esterne con data_fine ma stato < 3 → stato 3
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$fasi = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'f.ordine_id', '=', 'o.id')
    ->where('f.esterno', 1)
    ->whereNotNull('f.data_fine')
    ->whereRaw("f.stato REGEXP '^[0-9]+$' AND f.stato < 3")
    ->select('f.id', 'f.fase', 'f.stato', 'f.data_fine', 'o.commessa')
    ->get();

echo "Fasi esterne rientrate (data_fine presente, stato < 3): " . $fasi->count() . "\n\n";

foreach ($fasi as $f) {
    echo "  {$f->commessa} | {$f->fase} | stato:{$f->stato} → 3 | data_fine:{$f->data_fine}\n";
    DB::table('ordine_fasi')->where('id', $f->id)->update(['stato' => 3]);
}

echo "\nAggiornate: " . $fasi->count() . "\n";
