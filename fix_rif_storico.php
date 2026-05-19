<?php
/**
 * Ripristina ordine_cliente per ordini Maxtris persi quando Maxtris ha rimosso
 * righe dall'Excel ORDINE ASTUCCI.xlsx il 19/05/2026.
 * Dati estratti dall'Excel del 18/05/2026 (check_xlsx_67201.php output).
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// commessa(senza zero) → [pattern_descrizione_LIKE => rif]
$map = [
    '67201' => [
        '%DOLCE SPOSA%'         => 'P01267',
        '%SPOSA NOVELLA%'       => 'P01267',
        '%AVOLA SILVER%'        => 'P01284',
        '%TWO MILK CLASSICO ROSA%' => 'P01367',
    ],
    '67203' => [
        '%DOLCE SPOSA%'         => 'P01267',
        '%SPOSA NOVELLA%'       => 'P01267',
    ],
    '67163' => [
        '%SBAGLIATO%TIRAMIS%'   => 'P01338',
        '%NOISETTES%CARTA%'     => 'P01338',
    ],
    '67194' => [
        '%NUANCE SUNSET%'       => 'P01354',
        '%SPRITZ%'              => 'P01354',
        '%NUANCE GARDEN%'       => 'P01367',
        '%CAFF%ESPRESSO%'       => 'P01367',
    ],
    '67291' => [
        '%CATALOGO%WEDDING%'    => 'P01391',
        '%PATISSERIE%REGAL%'    => 'P01338',
    ],
];

$tot = 0;
foreach ($map as $commCorta => $articoli) {
    $commPadded = str_pad($commCorta, 7, '0', STR_PAD_LEFT) . '-26';
    foreach ($articoli as $pattern => $rif) {
        $rows = DB::table('ordini')
            ->where('commessa', $commPadded)
            ->where('descrizione', 'LIKE', $pattern)
            ->where(function($q) {
                $q->whereNull('ordine_cliente')->orWhere('ordine_cliente', '');
            })
            ->update(['ordine_cliente' => $rif]);
        if ($rows > 0) {
            echo "  $commPadded | $pattern → $rif ($rows ordini)\n";
            $tot += $rows;
        }
    }
}

echo "\nTotale ordini ripristinati: $tot\n";
