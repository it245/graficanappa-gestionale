<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$q = $argv[1] ?? '66363';

echo "Cerco '{$q}' su Onda (ATTDocTeste)...\n\n";

$risultati = DB::connection('onda')->select("
    SELECT TOP 10 t.CodCommessa, t.TipoDocumento, t.DataRegistrazione,
           COALESCE(a.RagioneSociale, '') AS Cliente
    FROM ATTDocTeste t
    LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
    WHERE t.CodCommessa LIKE ?
    ORDER BY t.DataRegistrazione DESC
", ["%{$q}%"]);

if (empty($risultati)) {
    echo "Nessun risultato.\n";
} else {
    foreach ($risultati as $r) {
        echo "{$r->CodCommessa} | Tipo: {$r->TipoDocumento} | Data: {$r->DataRegistrazione} | {$r->Cliente}\n";
    }
}
