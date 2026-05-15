<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\ClicheMatchService;

$index = ClicheMatchService::buildClicheIndex();
$singleLong = [];
$singleShort = [];
foreach ($index['list'] as $c) {
    if ($c['ntok'] === 1) {
        $tok = $c['tokens'][0];
        $info = "{$c['numero']} → $tok";
        if (mb_strlen($tok) >= 5) $singleLong[] = $info;
        else $singleShort[] = $info;
    }
}

echo "Cliché single-token con parola LUNGA (>=5 char) — saranno matchati con fix:\n";
foreach ($singleLong as $s) echo "  $s\n";
echo "\nTotale: " . count($singleLong) . "\n\n";

echo "Cliché single-token con parola CORTA (<5 char) — restano esclusi:\n";
foreach ($singleShort as $s) echo "  $s\n";
echo "\nTotale: " . count($singleShort) . "\n";
