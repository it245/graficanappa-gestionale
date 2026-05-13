<?php
// Uso: php cerca_dettaglio_onda.php 67438
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cerca = $argv[1] ?? '67438';

echo "=== ONDA: Teste + Righe per commessa $cerca ===\n\n";

// Cerca teste
$teste = DB::connection('onda')->select(
    "SELECT TOP 5 IdDocumento, CodCommessa, DataRegistrazione, TipoDocumento
     FROM ATTDocTeste
     WHERE CodCommessa LIKE ?
     ORDER BY DataRegistrazione DESC",
    ["%$cerca%"]
);

foreach ($teste as $t) {
    echo "TESTA Id={$t->IdDocumento} Commessa={$t->CodCommessa} Tipo={$t->TipoDocumento}\n";

    // Righe della testa
    $righe = DB::connection('onda')->select(
        "SELECT * FROM ATTDocRighe WHERE IdDocumento = ? ORDER BY Riga",
        [$t->IdDocumento]
    );
    echo "  Righe: " . count($righe) . "\n";

    foreach ($righe as $i => $r) {
        echo "  --- Riga " . ($i+1) . " ---\n";
        foreach ((array)$r as $k => $v) {
            if ($v !== null && $v !== '' && !is_object($v)) {
                $vs = is_string($v) ? substr($v, 0, 80) : $v;
                echo "    $k: $vs\n";
            }
        }
    }
    echo "\n";
}
