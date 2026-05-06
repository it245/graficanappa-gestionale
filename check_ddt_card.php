<?php
// Trova DDT/ordini a CARD S.R.L. (o altro fornitore) con riferimento a commessa
// nella descrizione (NON necessariamente in CodCommessa).
// Uso: php check_ddt_card.php "CARD" "67269"
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$fornitore = $argv[1] ?? 'CARD';
$rifCommessa = $argv[2] ?? '67269';

echo "=== Documenti Onda verso fornitore '$fornitore' (ultimi 60gg) ===\n";

$docs = DB::connection('onda')->select("
    SELECT t.IdDoc, t.TipoDocumento, t.NumeroDocumento, t.DataDocumento,
           an.RagioneSociale AS Anagrafica, t.CodCommessa AS CodCommessaTesta
    FROM ATTDocTeste t
    LEFT JOIN STDAnagrafiche an ON t.IdAnagrafica = an.IdAnagrafica
    WHERE an.RagioneSociale LIKE ?
      AND t.TipoDocumento IN ('3','6','7')
      AND t.DataDocumento >= DATEADD(day, -60, GETDATE())
    ORDER BY t.DataDocumento DESC
", ["%$fornitore%"]);

if (empty($docs)) {
    echo "  Nessun documento trovato.\n";
    exit(0);
}

$tipi = [3 => 'DDT vendita', 6 => 'Ordine fornitore', 7 => 'DDT fornitore'];

foreach ($docs as $d) {
    $tipoNome = $tipi[(int)$d->TipoDocumento] ?? "Tipo {$d->TipoDocumento}";
    $data = substr($d->DataDocumento, 0, 10);
    $codComm = $d->CodCommessaTesta ?? '-';
    echo "\n  IdDoc={$d->IdDoc} | Tipo={$d->TipoDocumento} ({$tipoNome}) | Num={$d->NumeroDocumento} | {$data} | Cliente/Forn: {$d->Anagrafica} | CodCommessa(testa)={$codComm}\n";

    // Mostra righe del documento
    $righe = DB::connection('onda')->select("
        SELECT NrRiga, CodArt, Descrizione, Qta, CodUnMis, CodCommessa
        FROM ATTDocRighe
        WHERE IdDoc = ?
        ORDER BY NrRiga
    ", [$d->IdDoc]);

    foreach ($righe as $r) {
        $rifRiga = $r->CodCommessa ?? '-';
        $marker = (str_contains($r->Descrizione ?? '', $rifCommessa) || $rifRiga === $rifCommessa || $rifRiga === '00' . $rifCommessa . '-26') ? ' ★' : '';
        $desc = substr($r->Descrizione ?? '', 0, 90);
        echo "      [{$r->NrRiga}] {$r->CodArt} | qta={$r->Qta} {$r->CodUnMis} | comm={$rifRiga} | {$desc}{$marker}\n";
    }
}
