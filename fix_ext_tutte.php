<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use App\Models\Reparto;
use App\Services\OndaSyncService;
use Illuminate\Support\Facades\DB;

$mappaReparti = OndaSyncService::getMappaReparti();

echo "=== FIX FASI EXT: rinomina da PRDDocFasi a ATTDocRighe ===" . PHP_EOL;
echo "Data: " . date('d/m/Y H:i') . PHP_EOL . PHP_EOL;

// Tutte le fasi EXT nel MES (attive, non consegnate)
$fasiExt = OrdineFase::with('ordine')
    ->where('fase', 'LIKE', 'EXT%')
    ->where('stato', '<', 4)
    ->get();

echo "Fasi EXT trovate: " . $fasiExt->count() . PHP_EOL . PHP_EOL;

$rinominate = 0;
$eliminateDup = 0;
$nonTrovate = 0;

foreach ($fasiExt as $fase) {
    $commessa = $fase->ordine->commessa ?? null;
    if (!$commessa) continue;

    $faseNomeExt = $fase->fase; // es. EXTAllest.Manuale
    $faseNomeSenzaExt = substr($faseNomeExt, 3); // es. Allest.Manuale

    // Cerca in Onda ATTDocRighe se esiste la versione senza EXT
    $rigaAtt = DB::connection('onda')->select("
        SELECT TOP 1 r.CodArt
        FROM ATTDocRighe r
        JOIN ATTDocTeste t ON r.IdDoc = t.IdDoc
        WHERE t.CodCommessa = ?
          AND r.CodArt = ?
    ", [$commessa, $faseNomeSenzaExt]);

    if (empty($rigaAtt)) {
        // Non trovata in ATTDocRighe senza EXT — è una fase realmente esterna, lascia com'è
        $nonTrovate++;
        continue;
    }

    // Trovata! ATTDocRighe ha il nome senza EXT
    // Controlla se esiste già nel MES una fase con lo stesso nome senza EXT per questa commessa
    $faseDup = OrdineFase::whereHas('ordine', fn($q) => $q->where('commessa', $commessa))
        ->where('fase', $faseNomeSenzaExt)
        ->where('id', '!=', $fase->id)
        ->first();

    if ($faseDup) {
        // Esiste già — elimina la versione EXT (duplicato)
        echo "ELIMINA DUP: {$commessa} | {$faseNomeExt} (ID:{$fase->id}) — esiste già {$faseNomeSenzaExt} (ID:{$faseDup->id})" . PHP_EOL;
        $fase->delete();
        $eliminateDup++;
    } else {
        // Non esiste — rinomina la fase EXT al nome senza EXT
        $repartoNome = $mappaReparti[$faseNomeSenzaExt] ?? 'legatoria';
        $reparto = Reparto::firstOrCreate(['nome' => $repartoNome]);
        $faseCatalogo = FasiCatalogo::firstOrCreate(
            ['nome' => $faseNomeSenzaExt],
            ['reparto_id' => $reparto->id]
        );

        $vecchioNome = $fase->fase;
        $fase->fase = $faseNomeSenzaExt;
        $fase->fase_catalogo_id = $faseCatalogo->id;
        // Non settare esterno=0 — potrebbe essere stata mandata fuori dal capo
        $fase->save();

        echo "RINOMINA: {$commessa} | {$vecchioNome} → {$faseNomeSenzaExt} (reparto: {$repartoNome})" . PHP_EOL;
        $rinominate++;
    }
}

// Ricalcola stati per tutte le commesse modificate
$commesseModificate = $fasiExt->pluck('ordine.commessa')->unique()->filter();
foreach ($commesseModificate as $c) {
    \App\Services\FaseStatoService::ricalcolaCommessa($c);
}

echo PHP_EOL . "=== RIEPILOGO ===" . PHP_EOL;
echo "Rinominate: {$rinominate}" . PHP_EOL;
echo "Duplicate eliminate: {$eliminateDup}" . PHP_EOL;
echo "Lasciate com'è (realmente esterne): {$nonTrovate}" . PHP_EOL;
echo "DONE" . PHP_EOL;
