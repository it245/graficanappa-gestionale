<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commesse = ['0066555-26', '0066661-26', '0066475-26', '0066480-26', '0066656-26', '0066811-26'];

foreach ($commesse as $comm) {
    $aggiornati = DB::table('ordine_fasi')
        ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
        ->where('ordini.commessa', $comm)
        ->where('ordine_fasi.tipo_consegna', 'parziale')
        ->whereNull('ordine_fasi.deleted_at')
        ->update([
            'ordine_fasi.tipo_consegna' => 'totale',
            'ordine_fasi.stato' => 4,
            'ordine_fasi.data_fine' => now(),
        ]);

    // Metti tutte le fasi a stato 4
    $fasi = DB::table('ordine_fasi')
        ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
        ->where('ordini.commessa', $comm)
        ->whereNull('ordine_fasi.deleted_at')
        ->whereRaw("ordine_fasi.stato REGEXP '^[0-9]+$' AND ordine_fasi.stato < 4")
        ->update([
            'ordine_fasi.stato' => 4,
            'ordine_fasi.data_fine' => DB::raw("COALESCE(ordine_fasi.data_fine, NOW())"),
        ]);

    echo "{$comm}: BRT aggiornato ({$aggiornati}), fasi chiuse ({$fasi})\n";
}
