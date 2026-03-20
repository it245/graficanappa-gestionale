<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;

echo "=== FIX: esterno=1 solo se inviata a fornitore ===" . PHP_EOL;
echo "Data: " . date('d/m/Y H:i') . PHP_EOL . PHP_EOL;

// Tutte le fasi con esterno=1
$fasi = OrdineFase::with('ordine')
    ->where('esterno', 1)
    ->where('stato', '<', 4)
    ->get();

echo "Fasi esterno=1 attive: " . $fasi->count() . PHP_EOL . PHP_EOL;

$corrette = 0; // davvero inviate a fornitore
$fixate = 0;   // esterno tolto

foreach ($fasi as $f) {
    $commessa = $f->ordine->commessa ?? '?';

    // È stata effettivamente inviata a un fornitore?
    $inviatoAFornitore = false;

    // 1. Ha ddt_fornitore_id (marcata dal parser DDT)
    if (!empty($f->ddt_fornitore_id)) $inviatoAFornitore = true;

    // 2. Ha nota "Inviato a:" (marcata dall'owner manualmente)
    if (!$inviatoAFornitore && $f->note && preg_match('/Inviato a:/i', $f->note)) $inviatoAFornitore = true;

    if ($inviatoAFornitore) {
        $corrette++;
        continue;
    }

    // Non è stata inviata a fornitore → togli esterno
    $f->esterno = 0;
    $f->save();
    echo "FIX: {$commessa} | {$f->fase} (ID:{$f->id}) | stato:{$f->stato} → esterno:NO" . PHP_EOL;
    $fixate++;
}

// Ricalcola stati per commesse modificate
$commesseModificate = $fasi->where('esterno', 0)->pluck('ordine.commessa')->unique()->filter();
foreach ($commesseModificate as $c) {
    \App\Services\FaseStatoService::ricalcolaCommessa($c);
}

echo PHP_EOL . "=== RIEPILOGO ===" . PHP_EOL;
echo "Davvero inviate a fornitore: {$corrette}" . PHP_EOL;
echo "Esterno rimosso (non inviate): {$fixate}" . PHP_EOL;
echo "DONE" . PHP_EOL;
