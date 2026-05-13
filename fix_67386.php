<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OrdineFase;
use App\Models\Ordine;
use App\Services\FaseStatoService;

$ordine = Ordine::where('commessa', '0067386-26')->first();
if (!$ordine) { echo "Ordine non trovato\n"; exit; }

$tutte = OrdineFase::where('ordine_id', $ordine->id)->get(['id','fase','stato','qta_prod']);
echo "Fasi commessa 67386:\n";
foreach ($tutte as $t) {
    echo "  id={$t->id} fase=[{$t->fase}] stato={$t->stato} qta={$t->qta_prod}\n";
}

$fase = OrdineFase::where('ordine_id', $ordine->id)
    ->where('fase', 'like', '%STAMPA%')
    ->where('stato', 2)
    ->first();
if (!$fase) { echo "\nFase STAMPA stato 2 non trovata\n"; exit; }
echo "\nTrovata: id={$fase->id} fase=[{$fase->fase}]\n";

echo "PRIMA: stato={$fase->stato} qta={$fase->qta_prod} buoni={$fase->fogli_buoni} scarto={$fase->fogli_scarto} fine={$fase->data_fine}\n";

$fase->fogli_buoni  = 5120;
$fase->fogli_scarto = 126;
$fase->qta_prod     = 5120;
$fase->stato        = 3;
$fase->data_fine    = '2026-05-13 06:23:00';
$fase->save();

FaseStatoService::ricalcolaStati($fase->ordine_id);

$fase->refresh();
echo "DOPO:  stato={$fase->stato} qta={$fase->qta_prod} buoni={$fase->fogli_buoni} scarto={$fase->fogli_scarto} fine={$fase->data_fine}\n";
