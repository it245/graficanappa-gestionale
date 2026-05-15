<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\ClicheMatchService;

echo "=== Cliché 2203/2204 in DB ===\n";
$cl = DB::table('cliche_anagrafica')->whereIn('numero', [2203, 2204])->get();
foreach ($cl as $c) {
    echo "  {$c->numero} → '{$c->descrizione_raw}' qta={$c->qta} scat={$c->scatola}\n";
}

echo "\n=== Cliché con BON BON ===\n";
$cl2 = DB::table('cliche_anagrafica')->where('descrizione_raw', 'like', '%BON BON%')->get();
foreach ($cl2 as $c) {
    echo "  {$c->numero} → '{$c->descrizione_raw}'\n";
}

echo "\n=== Tokenize 'BON BON CREAM NUANCE SALVIA' (descrizione MES) ===\n";
$norm = ClicheMatchService::normCore(ClicheMatchService::stripRumore('AST.1 KG BON BON CREAM NUANCE SALVIA'));
$tok = ClicheMatchService::tokenize($norm);
echo "  Tokens MES: " . implode(' | ', $tok) . "\n";

echo "\n=== Tokenize 'CIO CIO BON BON CREAM' (cliché 2203) ===\n";
$norm2 = ClicheMatchService::normCore(ClicheMatchService::stripRumore('CIO CIO BON BON CREAM'));
$tok2 = ClicheMatchService::tokenize($norm2);
echo "  Tokens cliché: " . implode(' | ', $tok2) . "\n";

echo "\n=== Match test 'CIO CIO BON BON CREAM SFUMATO' (cliché 2204) ===\n";
$norm3 = ClicheMatchService::normCore(ClicheMatchService::stripRumore('CIO CIO BON BON CREAM SFUMATO'));
$tok3 = ClicheMatchService::tokenize($norm3);
echo "  Tokens cliché 2204: " . implode(' | ', $tok3) . "\n";
