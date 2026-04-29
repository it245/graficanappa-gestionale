<?php
/**
 * Esplorazione COMPLETA del database Onda (SQL Server)
 * Mappa tutte le tabelle, colonne, conteggio righe e righe di esempio.
 * Salva il risultato in storage/app/onda_mappa_completa.json
 *
 * Eseguire sul server: php esplora_onda_completo.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
error_reporting(E_ALL & ~E_DEPRECATED);

use Illuminate\Support\Facades\DB;

echo "=== ESPLORAZIONE COMPLETA DATABASE ONDA ===\n";
echo "Inizio: " . date('Y-m-d H:i:s') . "\n\n";

$risultato = [
    'data_esplorazione' => date('Y-m-d H:i:s'),
    'server' => env('ONDA_DB_HOST', '?'),
    'database' => env('ONDA_DB_DATABASE', '?'),
    'tabelle' => [],
    'statistiche' => [
        'totale_tabelle' => 0,
        'totale_viste' => 0,
        'totale_colonne' => 0,
    ],
];

// 1. Lista TUTTE le tabelle
echo "1. Carico lista tabelle...\n";
$tabelle = DB::connection('onda')->select("
    SELECT TABLE_NAME, TABLE_TYPE
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_CATALOG = DB_NAME()
    ORDER BY TABLE_TYPE, TABLE_NAME
");

echo "   Trovate " . count($tabelle) . " tabelle/viste\n\n";

$risultato['statistiche']['totale_tabelle'] = collect($tabelle)->where('TABLE_TYPE', 'BASE TABLE')->count();
$risultato['statistiche']['totale_viste'] = collect($tabelle)->where('TABLE_TYPE', 'VIEW')->count();

foreach ($tabelle as $i => $tab) {
    $nome = $tab->TABLE_NAME;
    $tipo = $tab->TABLE_TYPE;

    echo "  [" . ($i + 1) . "/" . count($tabelle) . "] $nome ($tipo)";

    $infoTabella = [
        'nome' => $nome,
        'tipo' => $tipo,
        'colonne' => [],
        'conteggio_righe' => 0,
        'righe_esempio' => [],
        'indici' => [],
    ];

    // 2. Colonne della tabella
    try {
        $colonne = DB::connection('onda')->select("
            SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH,
                   IS_NULLABLE, COLUMN_DEFAULT
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ", [$nome]);

        foreach ($colonne as $col) {
            $infoTabella['colonne'][] = [
                'nome' => $col->COLUMN_NAME,
                'tipo' => $col->DATA_TYPE,
                'lunghezza' => $col->CHARACTER_MAXIMUM_LENGTH,
                'nullable' => $col->IS_NULLABLE,
                'default' => $col->COLUMN_DEFAULT,
            ];
        }
        $risultato['statistiche']['totale_colonne'] += count($colonne);
    } catch (\Exception $e) {
        $infoTabella['errore_colonne'] = $e->getMessage();
    }

    // 3. Conteggio righe (solo tabelle, non viste — piu veloce)
    try {
        if ($tipo === 'BASE TABLE') {
            $count = DB::connection('onda')->selectOne("
                SELECT SUM(p.rows) AS cnt
                FROM sys.tables t
                INNER JOIN sys.partitions p ON t.object_id = p.object_id AND p.index_id IN (0, 1)
                WHERE t.name = ?
            ", [$nome]);
            $infoTabella['conteggio_righe'] = (int) ($count->cnt ?? 0);
        } else {
            // Per le viste, prova un COUNT con TOP per non bloccare
            $count = DB::connection('onda')->selectOne("SELECT COUNT_BIG(*) AS cnt FROM [$nome]");
            $infoTabella['conteggio_righe'] = (int) ($count->cnt ?? 0);
        }
    } catch (\Exception $e) {
        $infoTabella['conteggio_righe'] = -1;
        $infoTabella['errore_conteggio'] = $e->getMessage();
    }

    echo " — " . $infoTabella['conteggio_righe'] . " righe, " . count($infoTabella['colonne']) . " colonne";

    // 4. Righe di esempio (TOP 3) — solo se ha righe
    if ($infoTabella['conteggio_righe'] > 0) {
        try {
            $righe = DB::connection('onda')->select("SELECT TOP 3 * FROM [$nome]");
            foreach ($righe as $riga) {
                $infoTabella['righe_esempio'][] = (array) $riga;
            }
        } catch (\Exception $e) {
            $infoTabella['errore_esempio'] = $e->getMessage();
        }
    }

    // 5. Chiavi primarie e indici
    try {
        $pk = DB::connection('onda')->select("
            SELECT COL_NAME(ic.object_id, ic.column_id) AS colonna
            FROM sys.indexes i
            INNER JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
            WHERE i.is_primary_key = 1 AND OBJECT_NAME(i.object_id) = ?
        ", [$nome]);
        $infoTabella['chiavi_primarie'] = collect($pk)->pluck('colonna')->toArray();
    } catch (\Exception $e) {
        // ignora
    }

    echo "\n";

    $risultato['tabelle'][] = $infoTabella;
}

// 6. Foreign keys
echo "\n2. Carico foreign keys...\n";
try {
    $fks = DB::connection('onda')->select("
        SELECT
            fk.name AS fk_name,
            OBJECT_NAME(fk.parent_object_id) AS tabella_figlia,
            COL_NAME(fkc.parent_object_id, fkc.parent_column_id) AS colonna_figlia,
            OBJECT_NAME(fk.referenced_object_id) AS tabella_padre,
            COL_NAME(fkc.referenced_object_id, fkc.referenced_column_id) AS colonna_padre
        FROM sys.foreign_keys fk
        INNER JOIN sys.foreign_key_columns fkc ON fk.object_id = fkc.constraint_object_id
        ORDER BY tabella_figlia, fk.name
    ");
    $risultato['foreign_keys'] = collect($fks)->map(fn($fk) => (array) $fk)->toArray();
    echo "   Trovate " . count($fks) . " foreign keys\n";
} catch (\Exception $e) {
    $risultato['foreign_keys_errore'] = $e->getMessage();
    echo "   Errore FK: " . $e->getMessage() . "\n";
}

// 7. Stored procedures
echo "\n3. Carico stored procedures...\n";
try {
    $sps = DB::connection('onda')->select("
        SELECT ROUTINE_NAME, ROUTINE_TYPE, CREATED, LAST_ALTERED
        FROM INFORMATION_SCHEMA.ROUTINES
        WHERE ROUTINE_CATALOG = DB_NAME()
        ORDER BY ROUTINE_TYPE, ROUTINE_NAME
    ");
    $risultato['stored_procedures'] = collect($sps)->map(fn($sp) => (array) $sp)->toArray();
    echo "   Trovate " . count($sps) . " stored procedures/functions\n";
} catch (\Exception $e) {
    $risultato['stored_procedures_errore'] = $e->getMessage();
}

// Salva risultato
$outputPath = __DIR__ . '/storage/app/onda_mappa_completa.json';
file_put_contents($outputPath, json_encode($risultato, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "\n=== COMPLETATO ===\n";
echo "Tabelle: " . $risultato['statistiche']['totale_tabelle'] . "\n";
echo "Viste: " . $risultato['statistiche']['totale_viste'] . "\n";
echo "Colonne totali: " . $risultato['statistiche']['totale_colonne'] . "\n";
echo "Salvato in: $outputPath\n";
echo "Dimensione: " . round(filesize($outputPath) / 1024, 1) . " KB\n";
