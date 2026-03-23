<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use Illuminate\Support\Facades\DB;

echo "=== ALLINEA esterno con TipoRiga Onda ===" . PHP_EOL;
echo "Data: " . date('d/m/Y H:i') . PHP_EOL . PHP_EOL;

// Tutte le fasi attive nel MES
$fasi = OrdineFase::with('ordine')
    ->where('stato', '<', 4)
    ->get();

echo "Fasi attive totali: " . $fasi->count() . PHP_EOL . PHP_EOL;

$fixEsterno = 0;
$fixInterno = 0;
$nonTrovate = 0;
$giaOk = 0;
$mantenute = 0; // inviate manualmente, non toccare

foreach ($fasi as $fase) {
    $commessa = $fase->ordine->commessa ?? null;
    if (!$commessa) continue;

    $faseNome = $fase->fase;

    // Se è stata inviata manualmente (DDT o nota "Inviato a:"), non toccare
    if (!empty($fase->ddt_fornitore_id) || ($fase->note && preg_match('/Inviato a:/i', $fase->note))) {
        $mantenute++;
        continue;
    }

    // Cerca TipoRiga in Onda per questa fase/commessa
    $ondaFase = DB::connection('onda')->select("
        SELECT TOP 1 f.TipoRiga
        FROM PRDDocFasi f
        JOIN PRDDocTeste p ON f.IdDoc = p.IdDoc
        WHERE p.CodCommessa = ? AND f.CodFase = ?
    ", [$commessa, $faseNome]);

    if (empty($ondaFase)) {
        $nonTrovate++;
        continue;
    }

    $tipoRiga = (int)$ondaFase[0]->TipoRiga;
    $deveEssereEsterno = ($tipoRiga === 2);

    if ($deveEssereEsterno && !$fase->esterno) {
        $fase->esterno = 1;
        $fase->save();
        echo "→ ESTERNO: {$commessa} | {$faseNome} (TipoRiga=2)" . PHP_EOL;
        $fixEsterno++;
    } elseif (!$deveEssereEsterno && $fase->esterno) {
        $fase->esterno = 0;
        $fase->save();
        echo "→ INTERNO: {$commessa} | {$faseNome} (TipoRiga=1)" . PHP_EOL;
        $fixInterno++;
    } else {
        $giaOk++;
    }
}

echo PHP_EOL . "=== RIEPILOGO ===" . PHP_EOL;
echo "Già corrette: {$giaOk}" . PHP_EOL;
echo "Settate esterno (TipoRiga=2): {$fixEsterno}" . PHP_EOL;
echo "Settate interno (TipoRiga=1): {$fixInterno}" . PHP_EOL;
echo "Mantenute (inviate manualmente): {$mantenute}" . PHP_EOL;
echo "Non trovate in Onda: {$nonTrovate}" . PHP_EOL;
echo "DONE" . PHP_EOL;
