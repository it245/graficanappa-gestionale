<?php
// Fasi nel reparto "esterno" con stato 2 ma flag esterno=0 → setta esterno=1
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$repartoEsterno = DB::table('reparti')->where('nome', 'esterno')->first();
if (!$repartoEsterno) { echo "Reparto esterno non trovato\n"; exit(1); }

$fasi = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'f.ordine_id', '=', 'o.id')
    ->join('fasi_catalogo as fc', 'f.fase_catalogo_id', '=', 'fc.id')
    ->where('fc.reparto_id', $repartoEsterno->id)
    ->where(function ($q) {
        $q->where('f.esterno', 0)->orWhereNull('f.esterno');
    })
    ->select('f.id', 'f.fase', 'f.stato', 'f.esterno', 'o.commessa')
    ->get();

echo "Fasi nel reparto esterno ma con flag esterno=0: " . $fasi->count() . "\n\n";

foreach ($fasi as $f) {
    echo "  {$f->commessa} | {$f->fase} | stato:{$f->stato} | esterno:{$f->esterno} → 1\n";
    DB::table('ordine_fasi')->where('id', $f->id)->update(['esterno' => 1]);
}

echo "\nAggiornate: " . $fasi->count() . "\n";
