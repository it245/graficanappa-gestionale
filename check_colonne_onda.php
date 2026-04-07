<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Ultimi 10 DDT vendita (TipoDocumento=3)
echo "=== ULTIMI 10 DDT VENDITA ===\n";
$ddts = DB::connection('onda')->select("
    SELECT TOP 10 IdDoc, NumeroDocumento, DataDocumento, DataRegistrazione
    FROM ATTDocTeste
    WHERE TipoDocumento = 3
    ORDER BY DataDocumento DESC
");
foreach ($ddts as $d) {
    echo "  IdDoc={$d->IdDoc} Numero=[{$d->NumeroDocumento}] Data={$d->DataDocumento}\n";
}

// Cerca specificamente il DDT 860
echo "\n=== CERCA DDT 860 (varie forme) ===\n";
$cerca = DB::connection('onda')->select("
    SELECT IdDoc, NumeroDocumento, DataDocumento
    FROM ATTDocTeste
    WHERE TipoDocumento = 3
      AND (NumeroDocumento = '860' OR NumeroDocumento = '0000860' OR NumeroDocumento LIKE '%860%')
");
foreach ($cerca as $d) {
    echo "  IdDoc={$d->IdDoc} Numero=[{$d->NumeroDocumento}] Data={$d->DataDocumento}\n";
}
