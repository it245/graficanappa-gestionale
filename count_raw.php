<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== COUNT fasi stampa offset stato 3 (raw JOIN) ===\n";
$r = DB::select("
    SELECT COUNT(DISTINCT o.commessa) as n
    FROM ordine_fasi f
    INNER JOIN ordini o ON o.id = f.ordine_id
    WHERE f.stato = 3
      AND (f.fase LIKE 'STAMPAXL106%' OR f.fase LIKE 'STAMPA XL%' OR f.fase = 'STAMPA')
");
echo "Via fase LIKE: {$r[0]->n}\n";

echo "\n=== Distinct fase usate nelle fasi stampa offset stato 3 ===\n";
$r2 = DB::select("
    SELECT f.fase, COUNT(*) as n
    FROM ordine_fasi f
    INNER JOIN fasi_catalogo fc ON fc.id = f.fase_catalogo_id
    INNER JOIN reparti r ON r.id = fc.reparto_id
    WHERE f.stato = 3 AND LOWER(r.nome) = 'stampa offset'
    GROUP BY f.fase
    ORDER BY n DESC
");
foreach ($r2 as $row) echo "  {$row->fase}: {$row->n}\n";

echo "\n=== Count commesse via join reparto ===\n";
$r3 = DB::select("
    SELECT COUNT(DISTINCT o.commessa) as n
    FROM ordine_fasi f
    INNER JOIN ordini o ON o.id = f.ordine_id
    INNER JOIN fasi_catalogo fc ON fc.id = f.fase_catalogo_id
    INNER JOIN reparti r ON r.id = fc.reparto_id
    WHERE f.stato = 3 AND LOWER(r.nome) = 'stampa offset'
");
echo "Via reparto join: {$r3[0]->n}\n";
