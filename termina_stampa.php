<?php
// Termina fasi stampa XL a stato 2 per una commessa
// Uso: php termina_stampa.php 66878
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cerca = $argv[1] ?? null;
if (!$cerca) { echo "Uso: php termina_stampa.php 66878\n"; exit(1); }

$commessa = str_pad($cerca, 7, '0', STR_PAD_LEFT) . '-26';
if (strpos($cerca, '-') !== false) $commessa = $cerca;

$fasi = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'f.ordine_id', '=', 'o.id')
    ->where('o.commessa', $commessa)
    ->where('f.fase', 'LIKE', 'STAMPAXL%')
    ->where('f.stato', 2)
    ->select('f.id', 'f.fase', 'f.stato', 'f.qta_prod', 'o.descrizione')
    ->get();

if ($fasi->isEmpty()) {
    echo "Nessuna fase stampa XL a stato 2 per {$commessa}\n";
    exit(0);
}

foreach ($fasi as $f) {
    echo "  #{$f->id} | {$f->fase} | qta_prod:{$f->qta_prod} | " . substr($f->descrizione, 0, 50) . " → stato 3\n";
    DB::table('ordine_fasi')->where('id', $f->id)->update([
        'stato' => 3,
        'data_fine' => now()->format('Y-m-d H:i:s'),
    ]);
}

echo "Terminate: " . $fasi->count() . " fasi\n";

// Ricalcola stati commessa
App\Services\FaseStatoService::ricalcolaCommessa($commessa);
echo "Stati ricalcolati per {$commessa}\n";
