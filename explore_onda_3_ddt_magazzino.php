<?php
/**
 * Esplorazione Onda - Script 3: DDT, magazzino, costi, fatture
 * Eseguire sul server: php explore_onda_3_ddt_magazzino.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESPLORAZIONE ONDA: DDT, MAGAZZINO, COSTI ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Cerca tabelle DDT/spedizione/vendita
echo "--- 1. TABELLE DDT / VENDITA / FATTURA ---\n";
$tabs = DB::connection('onda')->select("
    SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_TYPE = 'BASE TABLE'
      AND (TABLE_NAME LIKE '%DDT%' OR TABLE_NAME LIKE '%Ven%' OR TABLE_NAME LIKE '%Fat%'
           OR TABLE_NAME LIKE '%Sped%' OR TABLE_NAME LIKE '%Consegn%' OR TABLE_NAME LIKE '%Doc%')
    ORDER BY TABLE_NAME
");
foreach ($tabs as $t) {
    try {
        $count = DB::connection('onda')->selectOne("SELECT COUNT(*) as cnt FROM [{$t->TABLE_NAME}]");
        printf("  %-40s %s righe\n", $t->TABLE_NAME, number_format($count->cnt));
    } catch (\Exception $e) {
        printf("  %-40s ERRORE\n", $t->TABLE_NAME);
    }
}

// 2. Cerca tabelle magazzino/giacenze
echo "\n--- 2. TABELLE MAGAZZINO / GIACENZE ---\n";
$tabs = DB::connection('onda')->select("
    SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_TYPE = 'BASE TABLE'
      AND (TABLE_NAME LIKE '%Mag%' OR TABLE_NAME LIKE '%Giace%' OR TABLE_NAME LIKE '%Stock%'
           OR TABLE_NAME LIKE '%Lott%' OR TABLE_NAME LIKE '%Movim%')
    ORDER BY TABLE_NAME
");
foreach ($tabs as $t) {
    try {
        $count = DB::connection('onda')->selectOne("SELECT COUNT(*) as cnt FROM [{$t->TABLE_NAME}]");
        printf("  %-40s %s righe\n", $t->TABLE_NAME, number_format($count->cnt));
    } catch (\Exception $e) {
        printf("  %-40s ERRORE\n", $t->TABLE_NAME);
    }
}

// 3. Cerca tabelle costi/preventivi/listini
echo "\n--- 3. TABELLE COSTI / LISTINI / PREVENTIVI ---\n";
$tabs = DB::connection('onda')->select("
    SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_TYPE = 'BASE TABLE'
      AND (TABLE_NAME LIKE '%Cost%' OR TABLE_NAME LIKE '%Prev%' OR TABLE_NAME LIKE '%Listin%'
           OR TABLE_NAME LIKE '%Prezz%' OR TABLE_NAME LIKE '%Tariff%' OR TABLE_NAME LIKE '%Marg%')
    ORDER BY TABLE_NAME
");
foreach ($tabs as $t) {
    try {
        $count = DB::connection('onda')->selectOne("SELECT COUNT(*) as cnt FROM [{$t->TABLE_NAME}]");
        printf("  %-40s %s righe\n", $t->TABLE_NAME, number_format($count->cnt));
    } catch (\Exception $e) {
        printf("  %-40s ERRORE\n", $t->TABLE_NAME);
    }
}

// 4. Per ogni tabella DDT/VEN principale, mostra struttura
$tabelleDettaglio = ['VENDocTeste', 'VENDocRighe', 'VENDocTestePreventivo', 'ACQDocTeste', 'ACQDocRighe'];
foreach ($tabelleDettaglio as $nome) {
    echo "\n--- STRUTTURA [{$nome}] ---\n";
    try {
        $cols = DB::connection('onda')->select("
            SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
            FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ", [$nome]);
        if (empty($cols)) {
            echo "  (tabella non trovata)\n";
            continue;
        }
        foreach ($cols as $c) {
            $len = $c->CHARACTER_MAXIMUM_LENGTH ? "({$c->CHARACTER_MAXIMUM_LENGTH})" : '';
            printf("  %-35s %s\n", $c->COLUMN_NAME, $c->DATA_TYPE . $len);
        }

        echo "  [TOP 2 righe]\n";
        $rows = DB::connection('onda')->select("SELECT TOP 2 * FROM [{$nome}]");
        foreach ($rows as $r) {
            $arr = (array)$r;
            foreach ($arr as $k => $v) {
                if ($v === null || $v === '') continue;
                if (strlen((string)$v) > 80) $v = substr((string)$v, 0, 80) . '...';
                echo "    $k = $v\n";
            }
            echo "    ---\n";
        }
    } catch (\Exception $e) {
        echo "  ERRORE: {$e->getMessage()}\n";
    }
}

// 5. Commesse recenti con i loro DDT (se esistono join possibili)
echo "\n\n--- 5. ESEMPIO: COMMESSE CON DDT (ultimi 30 giorni) ---\n";
try {
    $rows = DB::connection('onda')->select("
        SELECT TOP 10
            t.CodCommessa,
            t.DataRegistrazione,
            v.NumDoc AS NumeroDDT,
            v.DataDoc AS DataDDT,
            v.TipoDocumento AS TipoDDT,
            a.RagioneSociale AS Cliente
        FROM ATTDocTeste t
        LEFT JOIN VENDocTeste v ON t.CodCommessa = v.CodCommessa
        LEFT JOIN STDAnagrafiche a ON t.IdAnagrafica = a.IdAnagrafica
        WHERE t.TipoDocumento = '2'
          AND t.DataRegistrazione >= DATEADD(day, -30, GETDATE())
          AND v.NumDoc IS NOT NULL
        ORDER BY v.DataDoc DESC
    ");
    if (empty($rows)) {
        echo "  Nessun risultato (il join CodCommessa potrebbe non esistere in VENDocTeste)\n";
    }
    foreach ($rows as $r) {
        echo "  Commessa: {$r->CodCommessa} | DDT: {$r->NumeroDDT} | Data: {$r->DataDDT} | Cliente: {$r->Cliente}\n";
    }
} catch (\Exception $e) {
    echo "  ERRORE join DDT: {$e->getMessage()}\n";
    echo "  (Potrebbe servire un campo diverso per collegare commesse a DDT)\n";
}

// 6. Cerca relazioni tra tabelle (foreign keys)
echo "\n\n--- 6. FOREIGN KEYS ---\n";
try {
    $fks = DB::connection('onda')->select("
        SELECT
            fk.name AS FK_Nome,
            tp.name AS TabellaParent,
            cp.name AS ColonnaParent,
            tr.name AS TabellaRef,
            cr.name AS ColonnaRef
        FROM sys.foreign_keys fk
        INNER JOIN sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
        INNER JOIN sys.tables tp ON fkc.parent_object_id = tp.object_id
        INNER JOIN sys.columns cp ON fkc.parent_object_id = cp.object_id AND fkc.parent_column_id = cp.column_id
        INNER JOIN sys.tables tr ON fkc.referenced_object_id = tr.object_id
        INNER JOIN sys.columns cr ON fkc.referenced_object_id = cr.object_id AND fkc.referenced_column_id = cr.column_id
        ORDER BY tp.name
    ");
    foreach ($fks as $fk) {
        echo "  {$fk->TabellaParent}.{$fk->ColonnaParent} → {$fk->TabellaRef}.{$fk->ColonnaRef}\n";
    }
    if (empty($fks)) {
        echo "  (Nessuna FK definita — Onda probabilmente usa relazioni implicite)\n";
    }
} catch (\Exception $e) {
    echo "  ERRORE: {$e->getMessage()}\n";
}

echo "\n\nScript completato.\n";
echo "Salvare: php explore_onda_3_ddt_magazzino.php > storage/report_onda_ddt.txt\n";
