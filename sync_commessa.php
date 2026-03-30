<?php
// Uso: php sync_commessa.php 66437
// Cerca la commessa in Onda e nel MES, e se manca nel MES la sincronizza.
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cerca = $argv[1] ?? null;
if (!$cerca) {
    echo "Uso: php sync_commessa.php <numero_commessa>\n";
    exit(1);
}

echo "=== 1. Ricerca in ONDA ===\n";
$righe = DB::connection('onda')->select(
    "SELECT TOP 10 t.CodCommessa, t.DataRegistrazione, t.TipoDocumento
     FROM ATTDocTeste t
     WHERE t.CodCommessa LIKE ?
     ORDER BY t.DataRegistrazione DESC",
    ["%$cerca%"]
);

if (empty($righe)) {
    echo "Commessa '$cerca' NON trovata in Onda. Controlla il numero.\n";
    exit(1);
}

echo "Trovata in Onda:\n";
foreach ($righe as $r) {
    echo "  {$r->CodCommessa}  |  DataReg: {$r->DataRegistrazione}  |  Tipo: {$r->TipoDocumento}\n";
}

echo "\n=== 2. Ricerca nel MES ===\n";
$ordini = App\Models\Ordine::where('commessa', 'like', "%$cerca%")->get();
if ($ordini->isNotEmpty()) {
    echo "Commessa GIA' presente nel MES:\n";
    foreach ($ordini as $o) {
        $numFasi = $o->fasi()->count();
        echo "  ID: {$o->id} | {$o->commessa} | {$o->cliente_nome} | {$o->descrizione} | Fasi: {$numFasi}\n";
    }
    echo "\nNessuna sync necessaria.\n";
    exit(0);
}

echo "Commessa NON presente nel MES. Avvio sync...\n";

// Usa il CodCommessa esatto di Onda (es. "0066437-26") per la sync
$codCommessaOnda = $righe[0]->CodCommessa;
echo "Codice Onda esatto: $codCommessaOnda\n";

echo "\n=== 3. Sync da Onda ===\n";
$service = app(App\Services\OndaSyncService::class);
$result = $service->sincronizzaSingolaCommessa($codCommessaOnda);

if ($result['trovata'] ?? false) {
    echo "SYNC COMPLETATA!\n";
    echo "Messaggio: {$result['messaggio']}\n";
    if (!empty($result['fasi'])) {
        echo "Fasi create:\n";
        foreach ($result['fasi'] as $fase) {
            echo "  - $fase\n";
        }
    }

    // Verifica finale
    echo "\n=== 4. Verifica ===\n";
    $ordini = App\Models\Ordine::where('commessa', 'like', "%$cerca%")->get();
    foreach ($ordini as $o) {
        $numFasi = $o->fasi()->count();
        echo "  ID: {$o->id} | {$o->commessa} | {$o->cliente_nome} | {$o->descrizione} | Fasi: {$numFasi}\n";
    }
} else {
    echo "ERRORE sync: " . ($result['messaggio'] ?? 'Nessun dettaglio') . "\n";
}
