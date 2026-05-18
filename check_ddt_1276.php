<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Righe DDT 1276 da Onda ===\n";
$rows = DB::connection('onda')->select("
    SELECT r.NrRiga, r.TipoRiga, r.CodCommessa, r.Descrizione, r.Qta, r.CodUnMis, r.CodArt
    FROM ATTDocTeste t
    JOIN ATTDocRighe r ON r.IdDoc = t.IdDoc
    WHERE t.TipoDocumento = '3' AND t.NumeroDocumento = '0001276'
    ORDER BY r.NrRiga
");
foreach ($rows as $r) {
    if ($r->TipoRiga != 1) continue;
    echo "  riga={$r->NrRiga} commessa={$r->CodCommessa} | desc=" . substr($r->Descrizione, 0, 60) . " | cod_art={$r->CodArt}\n";
}
