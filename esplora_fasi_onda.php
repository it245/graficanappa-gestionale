<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESPLORAZIONE FASI ONDA: come distingue interne da esterne ===" . PHP_EOL;
echo "Data: " . date('d/m/Y H:i') . PHP_EOL . PHP_EOL;

// 1. Struttura PRDDocFasi — tutte le colonne
echo "--- 1. COLONNE PRDDocFasi ---" . PHP_EOL;
$cols = DB::connection('onda')->select("
    SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'PRDDocFasi'
    ORDER BY ORDINAL_POSITION
");
foreach ($cols as $c) {
    echo "  {$c->COLUMN_NAME} | {$c->DATA_TYPE}" . ($c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '') . " | nullable:{$c->IS_NULLABLE}" . PHP_EOL;
}

// 2. Esempio fase con EXT e senza EXT — mostra TUTTI i campi
echo PHP_EOL . "--- 2. CONFRONTO: fase EXT vs fase senza EXT ---" . PHP_EOL;

// Fase con EXT
$extFase = DB::connection('onda')->select("
    SELECT TOP 1 * FROM PRDDocFasi WHERE CodFase LIKE 'EXT%' AND CodFase != 'EXTALLEST.SHOPPER'
");
if (!empty($extFase)) {
    echo PHP_EOL . "FASE CON EXT: {$extFase[0]->CodFase}" . PHP_EOL;
    foreach ((array)$extFase[0] as $k => $v) {
        if ($v !== null && $v !== '' && $v !== 0 && $v !== '0' && $v !== 0.0) {
            echo "  {$k}: {$v}" . PHP_EOL;
        }
    }
}

// Fase senza EXT (stessa commessa se possibile)
$noExtFase = DB::connection('onda')->select("
    SELECT TOP 1 * FROM PRDDocFasi WHERE CodFase = 'STAMPA' AND IdDoc IN (
        SELECT IdDoc FROM PRDDocFasi WHERE CodFase LIKE 'EXT%'
    )
");
if (!empty($noExtFase)) {
    echo PHP_EOL . "FASE SENZA EXT (stessa commessa): {$noExtFase[0]->CodFase}" . PHP_EOL;
    foreach ((array)$noExtFase[0] as $k => $v) {
        if ($v !== null && $v !== '' && $v !== 0 && $v !== '0' && $v !== 0.0) {
            echo "  {$k}: {$v}" . PHP_EOL;
        }
    }
}

// 3. C'è un campo "Esternalizzata" o "Esterna" o flag?
echo PHP_EOL . "--- 3. CAMPI SOSPETTI (est/ext/extern/lavorazione) ---" . PHP_EOL;
$campiSospetti = DB::connection('onda')->select("
    SELECT COLUMN_NAME, TABLE_NAME
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE (COLUMN_NAME LIKE '%est%' OR COLUMN_NAME LIKE '%ext%' OR COLUMN_NAME LIKE '%extern%'
           OR COLUMN_NAME LIKE '%lavoraz%' OR COLUMN_NAME LIKE '%interno%' OR COLUMN_NAME LIKE '%tipo%fase%'
           OR COLUMN_NAME LIKE '%fornitore%' OR COLUMN_NAME LIKE '%terzista%')
    AND TABLE_NAME IN ('PRDDocFasi', 'PRDDocTeste', 'ATTDocRighe', 'ATTDocTeste')
    ORDER BY TABLE_NAME, COLUMN_NAME
");
foreach ($campiSospetti as $c) {
    echo "  {$c->TABLE_NAME}.{$c->COLUMN_NAME}" . PHP_EOL;
}

// 4. Valori unici del campo Esternalizzata (se esiste)
echo PHP_EOL . "--- 4. CAMPO Esternalizzata (se esiste) ---" . PHP_EOL;
try {
    $vals = DB::connection('onda')->select("
        SELECT DISTINCT Esternalizzata, COUNT(*) as cnt
        FROM PRDDocFasi
        GROUP BY Esternalizzata
    ");
    foreach ($vals as $v) {
        echo "  Esternalizzata=" . ($v->Esternalizzata ?? 'NULL') . " | cnt:{$v->cnt}" . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "  Campo non esiste: " . $e->getMessage() . PHP_EOL;
}

// 5. Tutti i CodFase unici con prefisso EXT
echo PHP_EOL . "--- 5. TUTTE LE FASI EXT UNICHE IN ONDA ---" . PHP_EOL;
$fasiExt = DB::connection('onda')->select("
    SELECT CodFase, COUNT(*) as cnt
    FROM PRDDocFasi
    WHERE CodFase LIKE 'EXT%'
    GROUP BY CodFase
    ORDER BY cnt DESC
");
foreach ($fasiExt as $f) {
    echo "  {$f->CodFase} | {$f->cnt} occorrenze" . PHP_EOL;
}

// 6. Fasi che esistono sia con che senza EXT
echo PHP_EOL . "--- 6. FASI CHE ESISTONO SIA CON CHE SENZA EXT ---" . PHP_EOL;
$doppie = DB::connection('onda')->select("
    SELECT DISTINCT f1.CodFase as conExt, SUBSTRING(f1.CodFase, 4, LEN(f1.CodFase)) as senzaExt
    FROM PRDDocFasi f1
    WHERE f1.CodFase LIKE 'EXT%'
    AND EXISTS (
        SELECT 1 FROM PRDDocFasi f2 WHERE f2.CodFase = SUBSTRING(f1.CodFase, 4, LEN(f1.CodFase))
    )
");
foreach ($doppie as $d) {
    echo "  Onda ha sia [{$d->conExt}] che [{$d->senzaExt}]" . PHP_EOL;
}

// 7. Tabella ATTDocRighe — c'è un campo per distinguere fase da materiale?
echo PHP_EOL . "--- 7. COLONNE ATTDocRighe (per capire tipo riga) ---" . PHP_EOL;
$colsAtt = DB::connection('onda')->select("
    SELECT COLUMN_NAME, DATA_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'ATTDocRighe'
    AND (COLUMN_NAME LIKE '%tipo%' OR COLUMN_NAME LIKE '%riga%' OR COLUMN_NAME LIKE '%fase%'
         OR COLUMN_NAME LIKE '%serviz%' OR COLUMN_NAME LIKE '%articol%' OR COLUMN_NAME LIKE '%categ%')
    ORDER BY ORDINAL_POSITION
");
foreach ($colsAtt as $c) {
    echo "  {$c->COLUMN_NAME} | {$c->DATA_TYPE}" . PHP_EOL;
}

// 8. TipoRiga in ATTDocRighe — valori unici
echo PHP_EOL . "--- 8. TipoRiga in ATTDocRighe ---" . PHP_EOL;
try {
    $tipi = DB::connection('onda')->select("
        SELECT DISTINCT TipoRiga, COUNT(*) as cnt FROM ATTDocRighe GROUP BY TipoRiga ORDER BY cnt DESC
    ");
    foreach ($tipi as $t) {
        echo "  TipoRiga={$t->TipoRiga} | {$t->cnt}" . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "  Campo non esiste" . PHP_EOL;
}

echo PHP_EOL . "=== FINE ESPLORAZIONE ===" . PHP_EOL;
