<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$commesse = $argv[1] ?? '0066475-26,0066480-26,0066555-26,0066562-26,0066566-26,0066622-26,0066656-26,0066661-26,0066810-26,0066811-26,0066818-26';
$lista = array_map('trim', explode(',', $commesse));

echo "=== CONSEGNE DA ONDA (DDT vendita) ===\n\n";

foreach ($lista as $comm) {
    // Cerca DDT vendita (Tipo=3 o Tipo=6)
    $ddts = DB::connection('onda')->select("
        SELECT d.NumDoc, d.DataDoc, d.Tipo, d.Stato,
               r.CodArt, r.Descrizione, r.Qta, r.CodUnMis
        FROM DOCTeste d
        JOIN DOCRighe r ON d.IdDoc = r.IdDoc
        WHERE d.CodCommessa = ?
          AND d.Tipo IN (3, 6, 7, 8)
        ORDER BY d.DataDoc
    ", [$comm]);

    if (empty($ddts)) {
        // Prova anche nella tabella PRDDocTeste per DDT
        $ddts2 = DB::connection('onda')->select("
            SELECT d.NumDoc, d.DataDoc, d.Tipo, d.Stato
            FROM DOCTeste d
            WHERE d.CodCommessa = ?
            ORDER BY d.Tipo, d.DataDoc
        ", [$comm]);

        echo "{$comm}: Nessun DDT vendita trovato\n";
        if (!empty($ddts2)) {
            echo "  Documenti trovati:\n";
            foreach ($ddts2 as $d) {
                echo "    Tipo:{$d->Tipo} | Num:{$d->NumDoc} | Data:{$d->DataDoc} | Stato:{$d->Stato}\n";
            }
        }
        echo "\n";
        continue;
    }

    echo "{$comm}:\n";
    foreach ($ddts as $d) {
        $data = $d->DataDoc ? \Carbon\Carbon::parse($d->DataDoc)->format('d/m/Y') : '-';
        echo "  DDT {$d->NumDoc} del {$data} — {$d->CodArt} | {$d->Descrizione} | Qta: {$d->Qta} {$d->CodUnMis}\n";
    }
    echo "\n";
}
