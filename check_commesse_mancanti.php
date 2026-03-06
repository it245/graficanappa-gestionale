<?php
/**
 * Confronta commesse Onda (dal 27/02/2026) con quelle nel DB MES.
 * Mostra le commesse presenti su Onda ma mancanti nel MES.
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Confronto commesse Onda vs MES...\n\n";

// Tutte le commesse aperte su Onda dalla data di sync
$ondaCommesse = DB::connection('onda')->select("
    SELECT DISTINCT t.CodCommessa, t.DataRegistrazione,
           COALESCE(a.RagioneSociale, '') AS Cliente
    FROM ATTDocTeste t
    LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
    WHERE t.TipoDocumento = '2'
      AND t.DataRegistrazione >= CAST('20260227' AS datetime)
    ORDER BY t.CodCommessa
");

// Tutte le commesse nel MES
$mesCommesse = DB::table('ordini')->pluck('commessa')->unique()->toArray();

$mancanti = 0;
$totale = count($ondaCommesse);

foreach ($ondaCommesse as $o) {
    $cod = trim($o->CodCommessa);
    if (!in_array($cod, $mesCommesse)) {
        echo "MANCANTE: {$cod} | {$o->DataRegistrazione} | {$o->Cliente}\n";
        $mancanti++;
    }
}

echo "\n--- Riepilogo ---\n";
echo "Commesse su Onda (dal 27/02): {$totale}\n";
echo "Mancanti nel MES: {$mancanti}\n";

if ($mancanti === 0) {
    echo "Tutto allineato!\n";
}
