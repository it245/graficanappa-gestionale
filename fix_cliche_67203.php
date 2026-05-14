<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ordine;
use App\Services\ClicheMatchService;

$num = $argv[1] ?? '67203';
$comm = str_pad(ltrim($num, '0'), 7, '0', STR_PAD_LEFT) . '-26';

echo "Reset cliché ordini $comm...\n";
$ordini = Ordine::where('commessa', $comm)->get();
foreach ($ordini as $o) {
    echo "  ordine {$o->id} desc=" . substr($o->descrizione ?? '', 0, 40) . " (prima: cliche={$o->cliche_numero} type={$o->cliche_match_type})\n";
    $o->cliche_numero = null;
    $o->cliche_match_type = null;
    $o->cliche_matched_at = null;
    $o->save();
}

echo "\nRe-run cliché match...\n";
$res = ClicheMatchService::matchAll();
echo "Matched: {$res['matched']} | Updated: {$res['updated']}\n";

echo "\nVerifica finale:\n";
foreach (Ordine::where('commessa', $comm)->get() as $o) {
    echo "  ordine {$o->id} desc=" . substr($o->descrizione ?? '', 0, 40) . " → cliche={$o->cliche_numero} ({$o->cliche_match_type})\n";
}
