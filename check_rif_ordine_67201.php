<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== MES ordini commessa 67201/67203 ===\n";
$rows = DB::table('ordini')
    ->where('commessa', 'LIKE', '%67201%')
    ->orWhere('commessa', 'LIKE', '%67203%')
    ->get(['id','commessa','cliente_nome','cod_art','ordine_cliente','descrizione']);
foreach ($rows as $r) {
    echo "  id={$r->id} | commessa={$r->commessa} | cliente={$r->cliente_nome} | cod_art={$r->cod_art} | ordine_cliente=" . ($r->ordine_cliente ?? 'NULL') . "\n";
    echo "    desc=" . substr($r->descrizione ?? '', 0, 80) . "\n";
}

echo "\n=== ONDA t.ncpordinecliente per 0067201-26 ===\n";
$onda = DB::connection('onda')->select("
    SELECT t.CodCommessa, t.ncpordinecliente, p.CodArt, p.OC_Descrizione
    FROM ATTDocTeste t
    INNER JOIN PRDDocTeste p ON p.CodCommessa = t.CodCommessa
    WHERE t.TipoDocumento = '2' AND t.CodCommessa = '0067201-26'
");
foreach ($onda as $r) {
    echo "  ordine_cliente='" . ($r->ncpordinecliente ?? '') . "' | cod_art={$r->CodArt}\n";
    echo "    desc=" . substr($r->OC_Descrizione ?? '', 0, 80) . "\n";
}

echo "\n=== MES ordini con ordine_cliente P012% (count) ===\n";
$count = DB::table('ordini')->where('ordine_cliente', 'LIKE', 'P012%')->orWhere('ordine_cliente', 'LIKE', 'P013%')->orWhere('ordine_cliente', 'LIKE', 'P014%')->count();
echo "  Ordini con ordine_cliente=P01xxx: $count\n";

echo "\n=== Top 10 ordine_cliente più comuni ===\n";
$top = DB::table('ordini')->whereNotNull('ordine_cliente')->where('ordine_cliente', '!=', '')->selectRaw('ordine_cliente, COUNT(*) as cnt')->groupBy('ordine_cliente')->orderByDesc('cnt')->limit(10)->get();
foreach ($top as $t) {
    echo "  {$t->ordine_cliente}: {$t->cnt}\n";
}
