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

echo "=== FIX ESTERNO: controlla su Onda se la fase è davvero esterna ===" . PHP_EOL;
echo "Data: " . date('d/m/Y H:i') . PHP_EOL . PHP_EOL;

// Tutte le fasi con esterno=1 attive
$fasi = OrdineFase::with('ordine')
    ->where('esterno', 1)
    ->where('stato', '<', 4)
    ->get();

echo "Fasi esterno=1 trovate: " . $fasi->count() . PHP_EOL . PHP_EOL;

$fixati = 0;
$giaCorretti = 0;

foreach ($fasi as $fase) {
    $commessa = $fase->ordine->commessa ?? null;
    if (!$commessa) continue;

    $faseNome = $fase->fase;

    // Cerca in Onda PRDDocFasi: la fase ha EXT nel nome?
    $ondaFase = DB::connection('onda')->select("
        SELECT TOP 1 f.CodFase
        FROM PRDDocFasi f
        JOIN PRDDocTeste p ON f.IdDoc = p.IdDoc
        WHERE p.CodCommessa = ?
          AND (f.CodFase = ? OR f.CodFase = ? OR f.CodFase LIKE ?)
    ", [$commessa, $faseNome, 'EXT' . $faseNome, '%' . $faseNome . '%']);

    if (empty($ondaFase)) continue;

    $codFaseOnda = $ondaFase[0]->CodFase;

    // Se in Onda ha il prefisso EXT → è davvero esterna, lascia com'è
    if (str_starts_with(strtoupper($codFaseOnda), 'EXT')) {
        $giaCorretti++;
        continue;
    }

    // In Onda NON ha EXT → non è esterna, correggi
    $repartoNome = $mappaReparti[$faseNome] ?? $mappaReparti[$codFaseOnda] ?? null;

    if (!$repartoNome || $repartoNome === 'esterno') {
        // Non trovato nella mappa o ancora mappato come esterno, skip
        continue;
    }

    $reparto = Reparto::firstOrCreate(['nome' => $repartoNome]);
    $catalogo = FasiCatalogo::where('nome', $faseNome)->first();
    if ($catalogo && $catalogo->reparto_id !== $reparto->id) {
        $catalogo->reparto_id = $reparto->id;
        $catalogo->save();
    }

    $fase->esterno = 0;
    $fase->save();

    echo "FIX: {$commessa} | {$faseNome} | Onda:{$codFaseOnda} → esterno:NO, reparto:{$repartoNome}" . PHP_EOL;
    $fixati++;
}

// Ricalcola stati
$commesseModificate = $fasi->where('esterno', 0)->pluck('ordine.commessa')->unique()->filter();
foreach ($commesseModificate as $c) {
    \App\Services\FaseStatoService::ricalcolaCommessa($c);
}

echo PHP_EOL . "=== RIEPILOGO ===" . PHP_EOL;
echo "Corrette (esterno → interno): {$fixati}" . PHP_EOL;
echo "Già corrette (davvero esterne in Onda): {$giaCorretti}" . PHP_EOL;
echo "DONE" . PHP_EOL;
