<?php
/**
 * Esplorazione Onda - Script 2: Articoli, fustelle, clienti, materiali
 * Eseguire sul server: php explore_onda_2_articoli.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESPLORAZIONE ONDA: ARTICOLI, FUSTELLE, CLIENTI ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Struttura tabella STDAnagrafiche (già usata, ma vediamo TUTTE le colonne)
echo "--- 1. STRUTTURA STDAnagrafiche (clienti/fornitori) ---\n";
$cols = DB::connection('onda')->select("
    SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'STDAnagrafiche'
    ORDER BY ORDINAL_POSITION
");
foreach ($cols as $c) {
    $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
    printf("  %-35s %-15s %s\n", $c->COLUMN_NAME, $c->DATA_TYPE . $len, $c->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL');
}

echo "\n--- 1b. ESEMPIO STDAnagrafiche (TOP 3) ---\n";
$rows = DB::connection('onda')->select("SELECT TOP 3 * FROM STDAnagrafiche");
foreach ($rows as $r) {
    print_r((array)$r);
    echo "---\n";
}

// 2. Cerca tabelle articoli
echo "\n--- 2. TABELLE CON 'ART' NEL NOME ---\n";
$artTabs = DB::connection('onda')->select("
    SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_NAME LIKE '%Art%' OR TABLE_NAME LIKE '%art%'
    ORDER BY TABLE_NAME
");
foreach ($artTabs as $t) {
    echo "  {$t->TABLE_NAME}\n";
}

// Per ogni tabella articoli, mostra struttura e sample
foreach ($artTabs as $t) {
    $nome = $t->TABLE_NAME;
    echo "\n--- STRUTTURA [{$nome}] ---\n";
    $cols = DB::connection('onda')->select("
        SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
        FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?
        ORDER BY ORDINAL_POSITION
    ", [$nome]);
    foreach ($cols as $c) {
        $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
        printf("  %-35s %s\n", $c->COLUMN_NAME, $c->DATA_TYPE . $len);
    }

    echo "  [TOP 2 righe]\n";
    try {
        $rows = DB::connection('onda')->select("SELECT TOP 2 * FROM [{$nome}]");
        foreach ($rows as $r) {
            $arr = (array)$r;
            // Mostra solo le prime 10 colonne per leggibilità
            $keys = array_slice(array_keys($arr), 0, 15);
            foreach ($keys as $k) {
                $v = $arr[$k] ?? '';
                if (strlen($v) > 60) $v = substr($v, 0, 60) . '...';
                echo "    $k = $v\n";
            }
            echo "    ---\n";
        }
    } catch (\Exception $e) {
        echo "    ERRORE: {$e->getMessage()}\n";
    }
}

// 3. Cerca colonne con "fust" o "FS" in qualunque tabella
echo "\n\n--- 3. COLONNE CON 'FUST' O 'FS' ---\n";
$fustCols = DB::connection('onda')->select("
    SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE COLUMN_NAME LIKE '%fust%' OR COLUMN_NAME LIKE '%FS%'
    ORDER BY TABLE_NAME
");
foreach ($fustCols as $c) {
    echo "  [{$c->TABLE_NAME}] {$c->COLUMN_NAME} ({$c->DATA_TYPE})\n";
}

// 4. PRDDocTeste — struttura completa (ordini di produzione, già usata)
echo "\n\n--- 4. STRUTTURA PRDDocTeste ---\n";
$cols = DB::connection('onda')->select("
    SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
    FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'PRDDocTeste'
    ORDER BY ORDINAL_POSITION
");
foreach ($cols as $c) {
    $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
    printf("  %-35s %s\n", $c->COLUMN_NAME, $c->DATA_TYPE . $len);
}

// 5. PRDDocFasi — struttura completa (fasi lavorazione)
echo "\n\n--- 5. STRUTTURA PRDDocFasi (fasi lavorazione) ---\n";
$cols = DB::connection('onda')->select("
    SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
    FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'PRDDocFasi'
    ORDER BY ORDINAL_POSITION
");
foreach ($cols as $c) {
    $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
    printf("  %-35s %s\n", $c->COLUMN_NAME, $c->DATA_TYPE . $len);
}

echo "\n  [TOP 3 righe PRDDocFasi]\n";
$rows = DB::connection('onda')->select("SELECT TOP 3 * FROM PRDDocFasi");
foreach ($rows as $r) {
    $arr = (array)$r;
    foreach ($arr as $k => $v) {
        if ($v === null || $v === '') continue;
        if (strlen($v) > 60) $v = substr($v, 0, 60) . '...';
        echo "    $k = $v\n";
    }
    echo "    ---\n";
}

// 6. PRDDocRighe — struttura completa (materiali/carte)
echo "\n\n--- 6. STRUTTURA PRDDocRighe (materiali) ---\n";
$cols = DB::connection('onda')->select("
    SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
    FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'PRDDocRighe'
    ORDER BY ORDINAL_POSITION
");
foreach ($cols as $c) {
    $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
    printf("  %-35s %s\n", $c->COLUMN_NAME, $c->DATA_TYPE . $len);
}

echo "\n  [TOP 3 righe PRDDocRighe]\n";
$rows = DB::connection('onda')->select("SELECT TOP 3 * FROM PRDDocRighe");
foreach ($rows as $r) {
    $arr = (array)$r;
    foreach ($arr as $k => $v) {
        if ($v === null || $v === '') continue;
        if (strlen($v) > 60) $v = substr($v, 0, 60) . '...';
        echo "    $k = $v\n";
    }
    echo "    ---\n";
}

// 7. PRDMacchinari — struttura e dati
echo "\n\n--- 7. STRUTTURA PRDMacchinari ---\n";
$cols = DB::connection('onda')->select("
    SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
    FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'PRDMacchinari'
    ORDER BY ORDINAL_POSITION
");
foreach ($cols as $c) {
    $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
    printf("  %-35s %s\n", $c->COLUMN_NAME, $c->DATA_TYPE . $len);
}

echo "\n  [TUTTE le macchine]\n";
$rows = DB::connection('onda')->select("SELECT CodMacchina, Descrizione, OC_FogliScartoIniz FROM PRDMacchinari ORDER BY CodMacchina");
foreach ($rows as $r) {
    printf("  %-20s %-40s Scarti: %s\n", $r->CodMacchina, $r->Descrizione ?? '', $r->OC_FogliScartoIniz ?? '-');
}

// 8. ATTDocTeste — colonne non ancora importate
echo "\n\n--- 8. STRUTTURA ATTDocTeste (commesse) ---\n";
$cols = DB::connection('onda')->select("
    SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
    FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ATTDocTeste'
    ORDER BY ORDINAL_POSITION
");
$giàImportate = ['CodCommessa', 'DataRegistrazione', 'TotMerce', 'ncpcommentoprestampa', 'ncprespocommessa', 'OC_CommentoProduz', 'ncpordinecliente', 'TipoDocumento', 'IdAnagrafica'];
foreach ($cols as $c) {
    $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
    $imported = in_array($c->COLUMN_NAME, $giàImportate) ? ' *** GIA IMPORTATA ***' : '';
    printf("  %-35s %-15s%s\n", $c->COLUMN_NAME, $c->DATA_TYPE . $len, $imported);
}

echo "\n\nScript completato.\n";
echo "Salvare output: php explore_onda_2_articoli.php > storage/report_onda_articoli.txt\n";
