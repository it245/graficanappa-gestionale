<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commesse = $argv[1] ?? '0066475-26,0066480-26,0066555-26,0066562-26,0066566-26,0066622-26,0066656-26,0066661-26,0066810-26,0066811-26,0066818-26';
$lista = array_map('trim', explode(',', $commesse));

echo "=== DDT VENDITA DA ONDA ===\n\n";

foreach ($lista as $comm) {
    $ddts = DB::connection('onda')->select("
        SELECT t.IdDoc, t.NumeroDocumento, t.DataDocumento,
               a.RagioneSociale AS Cliente,
               v.RagioneSociale AS Vettore,
               r.Descrizione, r.Qta, r.CodUnMis, r.CodCommessa
        FROM ATTDocTeste t
        JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
        LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
        LEFT JOIN ATTDocCoda c ON t.IdDoc = c.IdDoc
        LEFT JOIN STDAnagrafiche v ON c.IdVettore1 = v.IdAnagrafica
        WHERE r.CodCommessa = ?
          AND t.TipoDocumento = 3
          AND r.TipoRiga = 1
        ORDER BY t.DataDocumento
    ", [$comm]);

    if (empty($ddts)) {
        echo "{$comm}: Nessun DDT vendita\n\n";
        continue;
    }

    $qtaTot = 0;
    echo "{$comm} ({$ddts[0]->Cliente}):\n";
    foreach ($ddts as $d) {
        $data = $d->DataDocumento ? \Carbon\Carbon::parse($d->DataDocumento)->format('d/m/Y') : '-';
        $desc = substr($d->Descrizione ?? '-', 0, 50);
        echo "  DDT {$d->NumeroDocumento} del {$data} | Qta: {$d->Qta} {$d->CodUnMis} | {$desc}\n";
        if ($d->Vettore) echo "    Vettore: {$d->Vettore}\n";
        $qtaTot += $d->Qta;
    }
    echo "  TOTALE CONSEGNATO: {$qtaTot}\n\n";
}
