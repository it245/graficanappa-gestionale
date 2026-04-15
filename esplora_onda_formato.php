<?php
/**
 * Esplora DB Onda per trovare tabelle/colonne relative a:
 * formato carta, base, altezza, famiglia supporto
 * Eseguire sul server: php esplora_onda_formato.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESPLORAZIONE ONDA: formato carta, base, altezza, famiglia supporto ===\n\n";

// 1. Cerca colonne con nomi rilevanti in tutte le tabelle
echo "--- 1. COLONNE CON NOME RILEVANTE ---\n";
$colonne = DB::connection('onda')->select("
    SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE COLUMN_NAME LIKE '%base%'
       OR COLUMN_NAME LIKE '%altez%'
       OR COLUMN_NAME LIKE '%largh%'
       OR COLUMN_NAME LIKE '%lung%'
       OR COLUMN_NAME LIKE '%formato%'
       OR COLUMN_NAME LIKE '%famiglia%'
       OR COLUMN_NAME LIKE '%supporto%'
       OR COLUMN_NAME LIKE '%dimens%'
       OR COLUMN_NAME LIKE '%CmBase%'
       OR COLUMN_NAME LIKE '%CmAlt%'
    ORDER BY TABLE_NAME, COLUMN_NAME
");
foreach ($colonne as $c) {
    echo "  {$c->TABLE_NAME}.{$c->COLUMN_NAME} ({$c->DATA_TYPE})\n";
}

// 2. Cerca tabelle con "Art" e "Fam" nel nome
echo "\n--- 2. TABELLE ARTICOLI/FAMIGLIE ---\n";
$tabelle = DB::connection('onda')->select("
    SELECT TABLE_NAME
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_NAME LIKE '%Art%'
       OR TABLE_NAME LIKE '%Fam%'
       OR TABLE_NAME LIKE '%Supp%'
       OR TABLE_NAME LIKE '%Lavor%'
    ORDER BY TABLE_NAME
");
foreach ($tabelle as $t) {
    echo "  {$t->TABLE_NAME}\n";
}

// 3. Per una commessa di esempio (66998), vediamo cosa c'è in PRDDocRighe
echo "\n--- 3. PRDDocRighe PER COMMESSA 66998 ---\n";
$righe = DB::connection('onda')->select("
    SELECT TOP 5 r.*
    FROM PRDDocRighe r
    INNER JOIN PRDDocTeste p ON r.IdDoc = p.IdDoc
    WHERE p.CodCommessa = '0066998-26'
");
if (!empty($righe)) {
    $first = (array) $righe[0];
    echo "  Colonne: " . implode(', ', array_keys($first)) . "\n";
    foreach ($righe as $r) {
        $arr = (array) $r;
        echo "  CodArt={$arr['CodArt']} Desc=" . ($arr['Descrizione'] ?? '-') . " Qta={$arr['Qta']}\n";
    }
}

// 4. Cerchiamo articoli lavorazione per commessa 66998
echo "\n--- 4. ATTDocRighe PER COMMESSA 66998 ---\n";
$righeAtt = DB::connection('onda')->select("
    SELECT TOP 10 r.CodArt, r.Descrizione, r.Qta, r.CodUnMis, r.Totale
    FROM ATTDocRighe r
    INNER JOIN ATTDocTeste t ON r.IdDoc = t.IdDoc
    WHERE t.CodCommessa = '0066998-26'
");
foreach ($righeAtt as $r) {
    echo "  CodArt={$r->CodArt} Desc={$r->Descrizione} Qta={$r->Qta}\n";
}

// 5. Tabella STDArticoli - cerchiamo info formato per il cod_carta
echo "\n--- 5. STDArticoli PER COD_CARTA 00W.TR.ELR.70.0003 ---\n";
$art = DB::connection('onda')->select("
    SELECT TOP 1 *
    FROM STDArticoli
    WHERE CodArt = '00W.TR.ELR.70.0003'
");
if (!empty($art)) {
    $cols = (array) $art[0];
    foreach ($cols as $k => $v) {
        if ($v !== null && $v !== '' && $v !== 0 && $v !== '0') {
            echo "  {$k} = {$v}\n";
        }
    }
}

echo "\nDone.\n";
