<?php
// Controlla se le commesse con lavorazioni esterne attive hanno fasi già consegnate (stato 4)
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== COMMESSE ESTERNE CON FASI GIA CONSEGNATE ===\n\n";

// Commesse che hanno almeno una fase esterna attiva (stato < 4)
$commesse = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->where(function($q) {
        $q->where('ordine_fasi.esterno', 1)
          ->orWhere('ordine_fasi.note', 'LIKE', '%Inviato a:%');
    })
    ->whereNull('ordine_fasi.deleted_at')
    ->whereRaw("ordine_fasi.stato REGEXP '^[0-9]+$' AND ordine_fasi.stato < 4")
    ->distinct()
    ->pluck('ordini.commessa');

$trovate = 0;

foreach ($commesse as $commessa) {
    // Cerca fasi consegnate (stato 4) nella stessa commessa
    $consegnate = DB::table('ordine_fasi')
        ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
        ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
        ->join('reparti', 'fasi_catalogo.reparto_id', '=', 'reparti.id')
        ->where('ordini.commessa', $commessa)
        ->where('ordine_fasi.stato', 4)
        ->whereNull('ordine_fasi.deleted_at')
        ->select('ordini.commessa', 'ordini.cliente_nome', 'ordine_fasi.fase', 'reparti.nome as reparto', 'ordine_fasi.data_fine')
        ->get();

    if ($consegnate->isNotEmpty()) {
        $trovate++;
        echo "*** {$commessa} — {$consegnate->first()->cliente_nome} ***\n";
        echo "  Fasi consegnate:\n";
        foreach ($consegnate as $f) {
            echo "    {$f->fase} ({$f->reparto}) — consegnata il {$f->data_fine}\n";
        }

        // Mostra anche le fasi esterne ancora attive
        $esterne = DB::table('ordine_fasi')
            ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
            ->where('ordini.commessa', $commessa)
            ->where(function($q) {
                $q->where('ordine_fasi.esterno', 1)
                  ->orWhere('ordine_fasi.note', 'LIKE', '%Inviato a:%');
            })
            ->whereNull('ordine_fasi.deleted_at')
            ->whereRaw("ordine_fasi.stato REGEXP '^[0-9]+$' AND ordine_fasi.stato < 4")
            ->select('ordine_fasi.fase', 'ordine_fasi.stato')
            ->get();

        echo "  Fasi esterne ancora attive:\n";
        foreach ($esterne as $e) {
            echo "    {$e->fase} (stato {$e->stato})\n";
        }
        echo "\n";
    }
}

echo "=== RIEPILOGO ===\n";
echo "Commesse con esterne attive e fasi già consegnate: {$trovate}\n";
