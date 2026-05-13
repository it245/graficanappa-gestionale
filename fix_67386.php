<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\OrdineFase;
use App\Models\Ordine;
use App\Services\FaseStatoService;

$ordini = Ordine::where('commessa', '0067386-26')->get();
if ($ordini->isEmpty()) { echo "Ordini non trovati\n"; exit; }
echo "Ordini commessa 67386: " . $ordini->count() . "\n";

$ordineIds = $ordini->pluck('id')->toArray();

$tutte = OrdineFase::whereIn('ordine_id', $ordineIds)->get(['id','ordine_id','fase','stato','qta_prod']);
echo "Fasi totali: " . $tutte->count() . "\n";
foreach ($tutte as $t) {
    echo "  id={$t->id} ordine={$t->ordine_id} fase=[{$t->fase}] stato={$t->stato} qta={$t->qta_prod}\n";
}

$fase = OrdineFase::whereIn('ordine_id', $ordineIds)
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
