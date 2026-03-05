<?php
/**
 * Backfill DDT vendita da Onda per un range di numeri DDT.
 * Il sync normale guarda solo ultimi 7 giorni — questo importa DDT più vecchi.
 *
 * Uso: php backfill_ddt.php [da] [a] [--dry-run]
 * Es:  php backfill_ddt.php 409 509
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ordine;

$da = (int) ($argv[1] ?? 409);
$a  = (int) ($argv[2] ?? 509);
$dryRun = in_array('--dry-run', $argv);

if ($dryRun) echo "=== DRY RUN ===\n\n";
echo "Backfill DDT vendita da Onda: range {$da} - {$a}\n";
echo str_repeat('=', 80) . "\n\n";

// Query Onda: stessa struttura del sync, senza filtro data
$righeDDT = DB::connection('onda')->select("
    SELECT t.IdDoc, r.CodCommessa, t.DataDocumento, t.NumeroDocumento,
           a.RagioneSociale AS Cliente,
           v.RagioneSociale AS Vettore,
           SUM(r.Qta) AS QtaDDT
    FROM ATTDocTeste t
    JOIN ATTDocRighe r ON t.IdDoc = r.IdDoc
    LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
    LEFT JOIN ATTDocCoda c ON t.IdDoc = c.IdDoc
    LEFT JOIN STDAnagrafiche v ON c.IdVettore1 = v.IdAnagrafica
    WHERE t.TipoDocumento = 3
      AND CAST(t.NumeroDocumento AS INT) BETWEEN ? AND ?
      AND r.CodCommessa IS NOT NULL AND r.CodCommessa != ''
      AND r.TipoRiga = 1
    GROUP BY t.IdDoc, r.CodCommessa, t.DataDocumento, t.NumeroDocumento, a.RagioneSociale, v.RagioneSociale
    ORDER BY t.NumeroDocumento
", [$da, $a]);

echo "Righe DDT trovate su Onda: " . count($righeDDT) . "\n\n";

$aggiornati = 0;
$skippati = 0;
$nonTrovati = 0;

foreach ($righeDDT as $riga) {
    $codCommessa = trim($riga->CodCommessa ?? '');
    if (!$codCommessa) continue;

    $idDoc = $riga->IdDoc;
    $numDDT = trim($riga->NumeroDocumento ?? '');
    $vettore = trim($riga->Vettore ?? '');
    $qtaDDT = (float) ($riga->QtaDDT ?? 0);

    // Cerca ordine nel MES
    $ordine = Ordine::where('commessa', $codCommessa)->first();
    if (!$ordine) {
        $nonTrovati++;
        continue;
    }

    // Skip se ordine ha già un ddt_vendita_id
    if ($ordine->ddt_vendita_id) {
        $skippati++;
        continue;
    }

    $numInt = (int) $numDDT;
    $brt = stripos($vettore, 'BRT') !== false ? ' [BRT]' : '';
    echo "  DDT {$numInt} → {$codCommessa} ordine #{$ordine->id} (qta: {$qtaDDT}){$brt}\n";

    if (!$dryRun) {
        $ordine->update([
            'ddt_vendita_id'      => $idDoc,
            'numero_ddt_vendita'  => $numDDT,
            'vettore_ddt'         => $vettore,
            'qta_ddt_vendita'     => $qtaDDT,
        ]);
    }

    $aggiornati++;
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "Aggiornati: {$aggiornati}\n";
echo "Già con DDT (skip): {$skippati}\n";
echo "Commessa non nel MES: {$nonTrovati}\n";
if ($dryRun) echo "(dry-run, nessuna modifica)\n";
