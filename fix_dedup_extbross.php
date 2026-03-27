<?php
// Pulisce fasi EXTBROSS duplicate per commessa (tiene solo 1 per commessa per fase)
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== PULIZIA EXTBROSS DUPLICATI ===\n\n";

$duplicati = DB::select("
    SELECT of1.id, o.commessa, of1.fase, o.cod_art
    FROM ordine_fasi of1
    JOIN ordini o ON of1.ordine_id = o.id
    WHERE of1.fase LIKE 'EXTBROSS%'
    AND of1.deleted_at IS NULL
    AND of1.id NOT IN (
        SELECT MIN(of2.id)
        FROM ordine_fasi of2
        JOIN ordini o2 ON of2.ordine_id = o2.id
        WHERE of2.fase LIKE 'EXTBROSS%'
        AND of2.deleted_at IS NULL
        GROUP BY o2.commessa, of2.fase
    )
    ORDER BY o.commessa, of1.fase
");

if (empty($duplicati)) {
    echo "Nessun duplicato trovato.\n";
    exit(0);
}

echo "Trovati " . count($duplicati) . " duplicati da rimuovere:\n";
foreach ($duplicati as $d) {
    echo "  ID:{$d->id} | {$d->commessa} | {$d->fase} | Art:{$d->cod_art}\n";
}

echo "\nRimozione (soft delete)...\n";
$rimossi = 0;
foreach ($duplicati as $d) {
    DB::table('ordine_fasi')->where('id', $d->id)->update(['deleted_at' => now()]);
    $rimossi++;
}

echo "Rimossi: {$rimossi}\n";
