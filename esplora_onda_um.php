<?php
/**
 * Mostra tutte le combinazioni CodFase + CodUnMis usate in Onda
 * Eseguire sul server .60: php esplora_onda_um.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// 1. Tutte le unità di misura disponibili
echo "=== UNITA DI MISURA IN USO (PRDDocFasi) ===" . PHP_EOL;
$ums = DB::connection('onda')->select("
    SELECT CodUnMis, COUNT(*) as cnt
    FROM PRDDocFasi
    WHERE CodUnMis IS NOT NULL AND CodUnMis != ''
    GROUP BY CodUnMis
    ORDER BY cnt DESC
");
foreach ($ums as $u) {
    echo "  {$u->CodUnMis}: {$u->cnt} fasi" . PHP_EOL;
}

// 2. Combinazioni fase + unità di misura
echo PHP_EOL . "=== COMBINAZIONI FASE + UM (ultime commesse) ===" . PHP_EOL;
$combos = DB::connection('onda')->select("
    SELECT f.CodFase, f.CodUnMis, COUNT(*) as cnt,
           AVG(f.QtaDaLavorare) as media_qta
    FROM PRDDocFasi f
    INNER JOIN PRDDocTeste p ON f.IdDoc = p.IdDoc
    INNER JOIN ATTDocTeste t ON t.CodCommessa = p.CodCommessa
    WHERE t.DataRegistrazione >= CAST('20260101' AS datetime)
    GROUP BY f.CodFase, f.CodUnMis
    ORDER BY f.CodFase, f.CodUnMis
");
foreach ($combos as $c) {
    $media = number_format($c->media_qta, 0, ',', '.');
    echo "  {$c->CodFase} | {$c->CodUnMis} | {$c->cnt}x | media qta: {$media}" . PHP_EOL;
}

// 3. Tabella unità di misura se esiste
echo PHP_EOL . "=== TABELLA UNITA MISURA (se esiste) ===" . PHP_EOL;
try {
    $tabUm = DB::connection('onda')->select("
        SELECT * FROM STDUnitaMisura ORDER BY CodUnMis
    ");
    foreach ($tabUm as $u) {
        $vals = (array)$u;
        $nonNull = array_filter($vals, fn($v) => $v !== null && $v !== '');
        echo "  " . implode(' | ', $nonNull) . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "  Tabella non trovata, provo MAGUnitaMisura..." . PHP_EOL;
    try {
        $tabUm = DB::connection('onda')->select("
            SELECT * FROM MAGUnitaMisura ORDER BY CodUnMis
        ");
        foreach ($tabUm as $u) {
            $vals = (array)$u;
            $nonNull = array_filter($vals, fn($v) => $v !== null && $v !== '');
            echo "  " . implode(' | ', $nonNull) . PHP_EOL;
        }
    } catch (\Exception $e2) {
        echo "  Nessuna tabella UM trovata" . PHP_EOL;
    }
}

echo PHP_EOL . "=== FINE ===" . PHP_EOL;
