<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Ordine;
use App\Models\OrdineFase;

$comm = '0067235-26';
$dryRun = in_array('--dry', $argv);

// Descrizioni distinte da Onda (6 PRD)
$desc = DB::connection('onda')->select(
    "SELECT r.IdDoc, r.NrRiga, r.Descrizione
     FROM ATTDocRighe r
     INNER JOIN ATTDocTeste t ON t.IdDoc = r.IdDoc
     WHERE CAST(t.CodCommessa AS VARCHAR) = ?
       AND r.TipoRiga = 1
       AND r.CodArt = 'Astucci'
     ORDER BY r.NrRiga",
    [$comm]
);
$descrizioni = array_column((array)$desc, 'Descrizione');
echo "Descrizioni Onda: " . count($descrizioni) . "\n";

// Ordini attuali
$ordini = Ordine::where('commessa', $comm)->orderBy('id')->get();
$ordineTemplate = $ordini->first();
echo "Ordini MES attuali: " . $ordini->count() . "\n";

// Mappa fase → desc: gruppi (PI01, FIN01) ordinati per id (cronologico)
foreach (['PI01', 'FIN01'] as $faseCod) {
    echo "\n=== $faseCod ===\n";
    $fasi = OrdineFase::whereIn('ordine_id', $ordini->pluck('id'))
        ->where('fase', $faseCod)
        ->orderBy('id')
        ->get();
    echo "Fasi $faseCod: " . $fasi->count() . "\n";

    foreach ($fasi as $i => $f) {
        $targetDesc = $descrizioni[$i] ?? null;
        if (!$targetDesc) { echo "  [$i] no desc\n"; continue; }

        $ordineCorrente = Ordine::find($f->ordine_id);
        if ($ordineCorrente->descrizione === $targetDesc) {
            echo "  [$i] fase id={$f->id} → ordine {$ordineCorrente->id} OK\n";
            continue;
        }

        // Se ordine corrente ha SOLO questa fase (nessun'altra fase con stessa desc target),
        // possiamo aggiornare desc ordine. Altrimenti clona.
        $altriFasiSuOrdine = OrdineFase::where('ordine_id', $ordineCorrente->id)
            ->where('id', '!=', $f->id)
            ->whereIn('fase', ['PI01', 'FIN01'])
            ->count();

        if ($altriFasiSuOrdine === 0 && $ordineCorrente->descrizione !== $targetDesc) {
            // Aggiorno desc dell'ordine corrente
            echo "  [$i] fase id={$f->id} → update desc ordine {$ordineCorrente->id}\n";
            if (!$dryRun) {
                $ordineCorrente->descrizione = $targetDesc;
                $ordineCorrente->save();
            }
        } else {
            // Clono ordine con nuova desc, sposto fase
            echo "  [$i] fase id={$f->id} → clona ordine + sposto\n";
            if (!$dryRun) {
                $nuovo = $ordineTemplate->replicate();
                $nuovo->descrizione = $targetDesc;
                $nuovo->save();
                $f->ordine_id = $nuovo->id;
                $f->save();
            }
        }
    }
}

echo "\nFatto" . ($dryRun ? ' (DRY-RUN)' : '') . "\n";
