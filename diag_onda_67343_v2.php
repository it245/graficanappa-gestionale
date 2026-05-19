<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$pattern = '%67343%';

echo "=== PRDDocTeste 67343 ===\n";
try {
    $cols = DB::connection('onda')->select("SELECT TOP 1 * FROM PRDDocTeste");
    if ($cols) {
        $colsNames = array_keys((array)$cols[0]);
        $codCommessaCol = null;
        foreach ($colsNames as $cn) if (stripos($cn, 'commessa') !== false) { $codCommessaCol = $cn; break; }
        echo "Colonne PRDDocTeste: " . implode(', ', $colsNames) . "\n\n";
        echo "Colonna commessa: {$codCommessaCol}\n";
    }
    $rows = DB::connection('onda')->select("
        SELECT TOP 20 *
        FROM PRDDocTeste
        WHERE CodCommessa LIKE ? OR NumDoc LIKE ?
    ", [$pattern, $pattern]);
    echo "Trovati: " . count($rows) . "\n";
    foreach ($rows as $r) {
        $arr = (array)$r;
        $info = [];
        foreach (['IdDoc','NumDoc','CodCommessa','CodArt','Descrizione','Stato','Qta'] as $k) {
            if (isset($arr[$k])) $info[] = "$k=".substr($arr[$k] ?? '', 0, 50);
        }
        echo "  " . implode(' | ', $info) . "\n";
    }
} catch (\Throwable $e) {
    echo "ERR: " . $e->getMessage() . "\n";
}

echo "\n=== PRDDocFasi (fasi documenti) per IdDoc trovati ===\n";
try {
    $teste = DB::connection('onda')->select("SELECT TOP 10 IdDoc FROM PRDDocTeste WHERE CodCommessa LIKE ?", [$pattern]);
    foreach ($teste as $t) {
        $fasi = DB::connection('onda')->select("SELECT * FROM PRDDocFasi WHERE IdDoc = ?", [$t->IdDoc]);
        echo "  IdDoc={$t->IdDoc} ha " . count($fasi) . " fasi:\n";
        foreach ($fasi as $f) {
            $arr = (array)$f;
            $info = [];
            foreach (['NrFase','CodFase','Descrizione','Sequenza','Stato','CodMacchina'] as $k) {
                if (isset($arr[$k])) $info[] = "$k=".substr($arr[$k] ?? '', 0, 30);
            }
            echo "    " . implode(' | ', $info) . "\n";
        }
    }
} catch (\Throwable $e) {
    echo "ERR: " . $e->getMessage() . "\n";
}

echo "\n=== PRDDocRighe 67343 ===\n";
try {
    $rows = DB::connection('onda')->select("
        SELECT TOP 20 IdDoc, CodArt, Descrizione, Qta, CodCommessa
        FROM PRDDocRighe
        WHERE CodCommessa LIKE ?
    ", [$pattern]);
    foreach ($rows as $r) {
        echo "  IdDoc={$r->IdDoc} | cm={$r->CodCommessa} | art={$r->CodArt} | qta={$r->Qta} | " . substr($r->Descrizione ?? '', 0, 50) . "\n";
    }
    if (empty($rows)) echo "  (nessuna riga)\n";
} catch (\Throwable $e) {
    echo "ERR: " . $e->getMessage() . "\n";
}
