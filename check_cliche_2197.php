<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\ClicheMatchService;

// Cliché 2197 in anagrafica?
$cl = DB::table('cliche_anagrafica')->where('numero', 2197)->first();
echo "Cliché 2197 in DB:\n";
print_r((array)$cl);

// Cerca per LAUREA
$lauree = DB::table('cliche_anagrafica')
    ->where('articolo', 'like', '%LAUREA%')
    ->orWhere('descrizione', 'like', '%LAUREA%')
    ->get();
echo "\nCliché con LAUREA in anagrafica:\n";
foreach ($lauree as $l) {
    echo "  {$l->numero} | {$l->articolo}\n";
}

// Test match per descrizioni 67385
$descs = [
    'AST.1 KG LAUREA',
    'AST.1 KG MAXTRIS CLASSICO ROSSO',
    'AST.1 KG MAXTRIS CLASSICO AZZURRO',
];
$index = ClicheMatchService::buildClicheIndex();
foreach ($descs as $d) {
    $match = ClicheMatchService::match($d, $index);
    echo "  $d → " . ($match ?: 'NULL') . "\n";
}

// Mostra ordini commessa 67385 cliché attuali
echo "\nOrdini commessa 67385:\n";
$ords = DB::table('ordini')->where('commessa', '0067385-26')->get();
foreach ($ords as $o) {
    echo "  id={$o->id} | desc={$o->descrizione} | cliche={$o->cliche_numero} | match_type={$o->cliche_match_type}\n";
}
