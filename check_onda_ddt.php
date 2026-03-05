<?php
/**
 * Cerca DDT vendita su Onda per una commessa.
 * Uso: php check_onda_ddt.php 0066273-26
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commessa = $argv[1] ?? null;
if (!$commessa) {
    echo "Uso: php check_onda_ddt.php <commessa>\n";
    exit(1);
}

echo "Cerco DDT su Onda per commessa: {$commessa}\n\n";

$righe = DB::connection('onda')->select("
    SELECT t.NumeroDocumento, t.DataDocumento, t.IdDoc,
           v.RagioneSociale AS Vettore
    FROM ATTDocTeste t
    JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
    LEFT JOIN ATTDocCoda c ON t.IdDoc = c.IdDoc
    LEFT JOIN STDAnagrafiche v ON c.IdVettore1 = v.IdAnagrafica
    WHERE t.TipoDocumento = 3
      AND r.CodCommessa = ?
      AND r.TipoRiga = 1
", [$commessa]);

if (empty($righe)) {
    echo "Nessun DDT trovato su Onda per {$commessa}\n";
} else {
    foreach ($righe as $r) {
        $num = (int) $r->NumeroDocumento;
        $vettore = trim($r->Vettore ?? '-');
        $brt = stripos($vettore, 'BRT') !== false ? ' [BRT]' : '';
        echo "DDT {$num} - data: {$r->DataDocumento} - vettore: {$vettore}{$brt} (IdDoc: {$r->IdDoc})\n";
    }
}
