<?php
// Mostra tutti documenti Onda collegati ad una commessa (DDT, ordini fornitore, ecc).
// Uso: php check_ddt_fornitore.php 0067269-26
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cm = $argv[1] ?? '0067269-26';

$tipi = [
    1 => 'Offerta cliente',
    2 => 'Commessa cliente',
    3 => 'DDT vendita',
    4 => 'Tipo 4',
    5 => 'Fattura cliente',
    6 => 'Ordine fornitore',
    7 => 'DDT fornitore',
    8 => 'Fattura fornitore',
];

echo "=== Documenti Onda per commessa $cm ===\n";

// 1. Documenti TESTE con CodCommessa nel campo testa
$testeCommessa = DB::connection('onda')->select("
    SELECT t.IdDoc, t.TipoDocumento, t.NumeroDocumento, t.DataDocumento, t.DataRegistrazione,
           an.RagioneSociale AS Anagrafica
    FROM ATTDocTeste t
    LEFT JOIN STDAnagrafiche an ON t.IdAnagrafica = an.IdAnagrafica
    WHERE t.CodCommessa = ?
    ORDER BY t.DataRegistrazione DESC
", [$cm]);

echo "\n--- TESTE (documenti con CodCommessa nella testa) ---\n";
foreach ($testeCommessa as $t) {
    $tipoNome = $tipi[(int)$t->TipoDocumento] ?? "Tipo {$t->TipoDocumento}";
    $data = substr($t->DataDocumento ?? $t->DataRegistrazione, 0, 10);
    echo "  IdDoc={$t->IdDoc} | Tipo={$t->TipoDocumento} ({$tipoNome}) | Num={$t->NumeroDocumento} | {$data} | " . ($t->Anagrafica ?? '-') . "\n";
}

// 2. Documenti con CodCommessa nelle RIGHE (DDT, ordini fornitore tipicamente)
$righeCommessa = DB::connection('onda')->select("
    SELECT t.IdDoc, t.TipoDocumento, t.NumeroDocumento, t.DataDocumento,
           an.RagioneSociale AS Anagrafica,
           COUNT(r.IdRiga) AS NRighe
    FROM ATTDocTeste t
    INNER JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
    LEFT JOIN STDAnagrafiche an ON t.IdAnagrafica = an.IdAnagrafica
    WHERE r.CodCommessa = ?
    GROUP BY t.IdDoc, t.TipoDocumento, t.NumeroDocumento, t.DataDocumento, an.RagioneSociale
    ORDER BY t.DataDocumento DESC
", [$cm]);

echo "\n--- TESTE con righe collegate alla commessa ---\n";
foreach ($righeCommessa as $t) {
    $tipoNome = $tipi[(int)$t->TipoDocumento] ?? "Tipo {$t->TipoDocumento}";
    $data = substr($t->DataDocumento ?? '', 0, 10);
    echo "  IdDoc={$t->IdDoc} | Tipo={$t->TipoDocumento} ({$tipoNome}) | Num={$t->NumeroDocumento} | {$data} | " . ($t->Anagrafica ?? '-') . " | {$t->NRighe} righe\n";

    // Mostra dettaglio righe per DDT/ordini fornitore (tipi 6, 7)
    if (in_array((int)$t->TipoDocumento, [3, 6, 7])) {
        $righe = DB::connection('onda')->select("
            SELECT r.NrRiga, r.CodArt, r.Descrizione, r.Qta, r.CodUnMis, r.TipoRiga
            FROM ATTDocRighe r
            WHERE r.IdDoc = ? AND r.CodCommessa = ?
            ORDER BY r.NrRiga
        ", [$t->IdDoc, $cm]);
        foreach ($righe as $r) {
            $desc = substr($r->Descrizione ?? '', 0, 80);
            echo "      [{$r->NrRiga}] {$r->CodArt} | TipoRiga={$r->TipoRiga} | qta={$r->Qta} {$r->CodUnMis} | {$desc}\n";
        }
    }
}
