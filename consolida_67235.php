<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ordine;
use App\Models\OrdineFase;
use Illuminate\Support\Facades\DB;

$comm = '0067235-26';
$dryRun = in_array('--dry', $argv);

$ordini = Ordine::where('commessa', $comm)->orderBy('id')->get();
echo "Ordini MES: " . $ordini->count() . "\n";

// Raggruppa per descrizione (prime 50 chars come chiave)
$gruppi = [];
foreach ($ordini as $o) {
    $key = substr($o->descrizione ?? '', 0, 50);
    $gruppi[$key][] = $o;
}

echo "Gruppi descrizione: " . count($gruppi) . "\n";

foreach ($gruppi as $desc => $ordiniGruppo) {
    if (count($ordiniGruppo) <= 1) continue;
    $keep = $ordiniGruppo[0];
    $duplicates = array_slice($ordiniGruppo, 1);
    echo "\n--- Desc: " . substr($desc, 0, 40) . "... ---\n";
    echo "  Mantieni: id={$keep->id}\n";
    foreach ($duplicates as $dup) {
        echo "  Sposta fasi da id={$dup->id} → {$keep->id}\n";
        $fasi = OrdineFase::where('ordine_id', $dup->id)->get();
        foreach ($fasi as $f) {
            echo "    fase id={$f->id} ({$f->fase})\n";
            if (!$dryRun) {
                $f->ordine_id = $keep->id;
                $f->save();
            }
        }
        if (!$dryRun) {
            echo "  Elimino ordine duplicato id={$dup->id}\n";
            $dup->delete();
        }
    }
}

echo "\nFatto" . ($dryRun ? ' (DRY)' : '') . "\n";

// Verifica finale
$ordiniFinal = Ordine::where('commessa', $comm)->orderBy('id')->get();
echo "\nOrdini finali: " . $ordiniFinal->count() . "\n";
foreach ($ordiniFinal as $o) {
    $nFasi = OrdineFase::where('ordine_id', $o->id)->count();
    echo "  id={$o->id} desc=" . substr($o->descrizione ?? '', 0, 40) . " fasi=$nFasi\n";
}
