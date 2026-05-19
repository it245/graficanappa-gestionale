<?php
/**
 * Diagnostica filtro Analisi Costi per commessa 67201-26.
 * Mostra fasi e stato per capire perché HAVING esclude.
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== AGGREGATO per commessa 67201 ===\n";
$rows = DB::select("
    SELECT o.commessa,
           COUNT(f.id) AS nf,
           SUM(CASE WHEN f.stato REGEXP '^[0-9]+$' AND CAST(f.stato AS UNSIGNED) >= 3 THEN 1 ELSE 0 END) AS ok,
           SUM(CASE WHEN f.stato NOT REGEXP '^[0-9]+$' OR CAST(f.stato AS UNSIGNED) < 3 THEN 1 ELSE 0 END) AS ko
    FROM ordini o
    JOIN ordine_fasi f ON f.ordine_id = o.id
    WHERE o.commessa LIKE '%67201-26'
    GROUP BY o.commessa
");
foreach ($rows as $r) {
    echo "  {$r->commessa} | fasi={$r->nf} | OK>=3={$r->ok} | KO<3={$r->ko}\n";
}

echo "\n=== DETTAGLIO singole fasi 67201 ===\n";
$det = DB::select("
    SELECT o.commessa, o.descrizione, f.fase, f.stato,
           LENGTH(f.stato) AS lstato,
           HEX(f.stato) AS hex_stato
    FROM ordini o
    JOIN ordine_fasi f ON f.ordine_id = o.id
    WHERE o.commessa LIKE '%67201-26'
    ORDER BY o.id, f.id
");
echo "Totale righe: " . count($det) . "\n";
foreach ($det as $r) {
    echo "  {$r->commessa} | {$r->fase} | stato='{$r->stato}' (len={$r->lstato}, hex={$r->hex_stato}) | " . substr($r->descrizione ?? '-', 0, 40) . "\n";
}

echo "\n=== ORDINI senza fasi (orfani) ===\n";
$orfani = DB::select("
    SELECT o.id, o.commessa, o.descrizione
    FROM ordini o
    LEFT JOIN ordine_fasi f ON f.ordine_id = o.id
    WHERE o.commessa LIKE '%67201-26' AND f.id IS NULL
");
foreach ($orfani as $r) {
    echo "  id={$r->id} | {$r->commessa} | " . substr($r->descrizione ?? '-', 0, 40) . "\n";
}
echo "\n";
