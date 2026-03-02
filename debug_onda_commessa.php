<?php
// Interroga Onda per una commessa specifica e mostra fasi
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$commessa = $argv[1] ?? '0066557-26';
echo "=== Commessa: $commessa ===\n\n";

$righe = DB::connection('onda')->select("
    SELECT
        t.CodCommessa,
        p.CodArt,
        p.OC_Descrizione,
        p.QtaDaProdurre,
        f.CodFase,
        f.CodMacchina,
        f.QtaDaLavorare,
        f.CodUnMis AS UMFase
    FROM ATTDocTeste t
    INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
    LEFT JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
    WHERE t.TipoDocumento = '2'
      AND t.CodCommessa = ?
    ORDER BY p.OC_Descrizione, f.CodFase
", [$commessa]);

if (empty($righe)) {
    echo "Nessun dato trovato in Onda\n";
    exit;
}

echo "Trovate " . count($righe) . " righe\n\n";

$current = '';
foreach ($righe as $r) {
    $key = trim($r->CodArt) . ' | ' . trim($r->OC_Descrizione);
    if ($key !== $current) {
        $current = $key;
        echo "--- $key (Qta: {$r->QtaDaProdurre}) ---\n";
    }
    $fase = trim($r->CodFase ?? '(nessuna)');
    $macchina = trim($r->CodMacchina ?? '-');
    $qta = $r->QtaDaLavorare ?? 0;
    $um = trim($r->UMFase ?? '');
    echo "  $fase \t macchina=$macchina \t qta=$qta $um\n";
}

// Mostra anche le fasi nel MES
echo "\n=== Fasi nel MES ===\n";
$ordini = App\Models\Ordine::where('commessa', $commessa)->get();
foreach ($ordini as $ordine) {
    echo "\nOrdine #{$ordine->id}: {$ordine->descrizione}\n";
    $fasi = App\Models\OrdineFase::where('ordine_id', $ordine->id)->orderBy('priorita')->get();
    foreach ($fasi as $f) {
        echo "  {$f->fase} \t prio={$f->priorita} \t stato={$f->stato} \t qta_fase={$f->qta_fase} \t qta_prod={$f->qta_prod}\n";
    }
}
