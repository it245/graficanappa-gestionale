<?php
// Uso: php cerca_commessa.php 66575
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cerca = $argv[1] ?? '66575';

echo "=== Ricerca in ONDA ===\n";
$righe = DB::connection('onda')->select(
    "SELECT TOP 10 t.CodCommessa, t.DataRegistrazione, t.TipoDocumento
     FROM ATTDocTeste t
     WHERE t.CodCommessa LIKE ?
     ORDER BY t.DataRegistrazione DESC",
    ["%$cerca%"]
);

if (empty($righe)) {
    echo "Nessuna commessa trovata con '$cerca' in Onda.\n";
} else {
    foreach ($righe as $r) {
        echo "  {$r->CodCommessa}  |  DataReg: {$r->DataRegistrazione}  |  Tipo: {$r->TipoDocumento}\n";
    }
}

echo "\n=== Ricerca nel MES ===\n";
$ordini = App\Models\Ordine::where('commessa', 'like', "%$cerca%")->get();
if ($ordini->isEmpty()) {
    echo "Nessun ordine trovato nel MES con '$cerca'.\n";
} else {
    foreach ($ordini as $o) {
        echo "  {$o->commessa} | {$o->cliente_nome} | {$o->descrizione}\n";
    }
}
