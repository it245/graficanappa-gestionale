<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = $argv[1] ?? '66942';

echo "=== DDT Onda (TipoDoc 7) ultimi 60gg con riferimento {$commessa} ===\n";
$rows = DB::connection('onda')->select("
    SELECT t.IdDoc, t.DataDocumento, a.RagioneSociale AS Fornitore, r.Descrizione
    FROM ATTDocTeste t
    JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
    LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
    WHERE t.TipoDocumento = 7
      AND t.DataRegistrazione >= DATEADD(day, -60, GETDATE())
      AND r.Descrizione LIKE ?
", ["%{$commessa}%"]);

if (empty($rows)) echo "  (nessun DDT trovato)\n";
foreach ($rows as $r) {
    echo "  IdDoc={$r->IdDoc} data={$r->DataDocumento} forn='{$r->Fornitore}'\n";
    echo "    desc: " . mb_substr($r->Descrizione, 0, 200) . "\n\n";
}
