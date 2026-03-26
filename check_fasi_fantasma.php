<?php
// Mostra le fasi a stato 2 che non appaiono nella dashboard owner
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Fasi problematiche trovate dallo script verifica
$commesse = [
    // JOH Caldo
    '0066516-26', '0066517-26', '0066521-26', '0066523-26',
    '0066529-26', '0066530-26', '0066533-26', '0066535-26',
    '0066666-26', '0066739-26',
    // Plastificatrice
    '0066802-26', '0066819-26', '0066818-26', '0066822-26', '0066845-26', '0066856-26',
    // Fustella Cilindrica
    '0066507-26', '0066657-26', '0066667-26', '0066692-26',
    // Canon V900
    '0066755-26', '0066892-26',
    // BOBST
    '0066797-26',
];

echo "=== FASI A STATO 2 CHE NON APPAIONO IN DASHBOARD ===\n\n";

foreach (array_unique($commesse) as $comm) {
    $fasi = DB::table('ordine_fasi as f')
        ->join('ordini as o', 'f.ordine_id', '=', 'o.id')
        ->join('fasi_catalogo as fc', 'f.fase_catalogo_id', '=', 'fc.id')
        ->join('reparti as r', 'fc.reparto_id', '=', 'r.id')
        ->where('o.commessa', $comm)
        ->where('f.stato', 2)
        ->select('f.id', 'f.fase', 'f.stato', 'f.data_inizio', 'f.deleted_at',
                 'o.commessa', 'o.cliente_nome', 'r.nome as reparto')
        ->get();

    if ($fasi->isEmpty()) continue;

    foreach ($fasi as $f) {
        $del = $f->deleted_at ? " *** SOFT DELETED: {$f->deleted_at} ***" : "";
        echo "  {$f->commessa} | {$f->fase} | {$f->reparto} | stato:{$f->stato} | inizio:{$f->data_inizio} | id:{$f->id}{$del}\n";
    }
}
