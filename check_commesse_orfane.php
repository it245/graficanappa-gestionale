<?php
/**
 * Trova commesse Onda registrate ma senza PRD (Genera OPI mai fatto).
 * Uso: php check_commesse_orfane.php [giorni]
 *   giorni = quanti giorni indietro guardare (default 30)
 */
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$giorni = (int) ($argv[1] ?? 30);

echo "=== Commesse ATT senza PRD ultimi {$giorni}gg ===\n\n";

$orfane = DB::connection('onda')->select("
    SELECT
        a.CodCommessa,
        a.DataRegistrazione,
        a.IdDoc,
        an.RagioneSociale AS Cliente,
        DATEDIFF(day, a.DataRegistrazione, GETDATE()) AS GiorniFa
    FROM ATTDocTeste a
    LEFT JOIN STDAnagrafiche an ON a.IdAnagrafica = an.IdAnagrafica
    LEFT JOIN PRDDocTeste p ON a.CodCommessa = p.CodCommessa
    WHERE a.TipoDocumento = '2'
      AND a.DataRegistrazione >= DATEADD(day, -?, GETDATE())
      AND p.IdDoc IS NULL
    ORDER BY a.DataRegistrazione DESC
", [$giorni]);

if (empty($orfane)) {
    echo "Nessuna commessa orfana — tutto sync.\n";
    exit(0);
}

echo "TROVATE " . count($orfane) . " commesse SENZA PRD:\n\n";
echo str_pad("Commessa", 16) . str_pad("Data Reg", 14) . str_pad("Giorni fa", 10) . "Cliente\n";
echo str_repeat("-", 100) . "\n";

foreach ($orfane as $o) {
    $data = substr($o->DataRegistrazione, 0, 10);
    echo str_pad($o->CodCommessa, 16) . str_pad($data, 14) . str_pad($o->GiorniFa, 10) . ($o->Cliente ?? '-') . "\n";
}

echo "\n=== Verifica MES ===\n";
$mancanti = 0;
foreach ($orfane as $o) {
    $esiste = App\Models\Ordine::where('commessa', $o->CodCommessa)->exists();
    if (!$esiste) $mancanti++;
}
echo "Su " . count($orfane) . " ATT senza PRD, $mancanti NON in MES (effettivo gap).\n";
