<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$commCorto = '67343';
$commPadded = '0067343-26';

echo "=== Tabelle Onda con 'PRD' o 'Produzione' ===\n";
$tabs = DB::connection('onda')->select("
    SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_NAME LIKE '%PRD%' OR TABLE_NAME LIKE '%Produzione%' OR TABLE_NAME LIKE '%OrdProd%'
    ORDER BY TABLE_NAME
");
foreach ($tabs as $t) echo "  - {$t->TABLE_NAME}\n";

echo "\n=== Tabelle Onda con 'Commessa' ===\n";
$tabs2 = DB::connection('onda')->select("
    SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_NAME LIKE '%Commess%' OR TABLE_NAME LIKE '%Lavoraz%' OR TABLE_NAME LIKE '%Fas%'
    ORDER BY TABLE_NAME
");
foreach ($tabs2 as $t) echo "  - {$t->TABLE_NAME}\n";

echo "\n=== Cerca codice commessa 67343 in ANAMovCommessaRighe ===\n";
try {
    $rows = DB::connection('onda')->select("
        SELECT TOP 30 CodCommessa, CodSottocommessa, CodCausale, CodArt, CodLavInterna, CodLavEsterna, CodOperaio, CodMacchina, Qta, Ore
        FROM ANAMovCommessaRighe
        WHERE CodCommessa LIKE ?
    ", ['%'.$commCorto.'%']);
    foreach ($rows as $r) {
        echo "  cm={$r->CodCommessa} | sc={$r->CodSottocommessa} | art={$r->CodArt} | lavInt={$r->CodLavInterna} | lavExt={$r->CodLavEsterna} | macc={$r->CodMacchina} | qta={$r->Qta} | ore={$r->Ore}\n";
    }
    if (empty($rows)) echo "  (nessuna riga)\n";
} catch (\Throwable $e) {
    echo "  ERR: " . $e->getMessage() . "\n";
}

echo "\n=== Cerca in ANAViewPreventiviConsuntivi ===\n";
try {
    $rows = DB::connection('onda')->select("
        SELECT TOP 30 CodCommessa, CodArt, CodLavInterna, CodLavEsterna, CodMacchina, P_Qta, P_Ore, C_Qta, C_Ore
        FROM ANAViewPreventiviConsuntivi
        WHERE CodCommessa LIKE ?
    ", ['%'.$commCorto.'%']);
    foreach ($rows as $r) {
        echo "  cm={$r->CodCommessa} | art={$r->CodArt} | lavInt={$r->CodLavInterna} | lavExt={$r->CodLavEsterna} | macc={$r->CodMacchina} | P_Qta={$r->P_Qta} | C_Qta={$r->C_Qta}\n";
    }
    if (empty($rows)) echo "  (nessuna riga)\n";
} catch (\Throwable $e) {
    echo "  ERR: " . $e->getMessage() . "\n";
}
