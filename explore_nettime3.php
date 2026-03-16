<?php
/**
 * Esplora il database SQL Server di NetTime sul .34
 * Eseguire sul server .60: php explore_nettime3.php
 */

try {
    $conn = new PDO('sqlsrv:Server=192.168.1.34;Database=master', '', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== CONNESSO AL SQL SERVER .34 ===" . PHP_EOL;

    echo PHP_EOL . "=== DATABASE ===" . PHP_EOL;
    $dbs = $conn->query("SELECT name FROM sys.databases ORDER BY name");
    while ($row = $dbs->fetch(PDO::FETCH_ASSOC)) {
        echo "  " . $row['name'] . PHP_EOL;
    }

    // Cerca database utente (non di sistema)
    $dbs = $conn->query("SELECT name FROM sys.databases WHERE name NOT IN ('master','tempdb','model','msdb')");
    $found = [];
    while ($row = $dbs->fetch(PDO::FETCH_ASSOC)) {
        $found[] = $row['name'];
    }

    if (empty($found)) {
        echo PHP_EOL . "Nessun database utente trovato." . PHP_EOL;
        exit;
    }

    foreach ($found as $dbName) {
        echo PHP_EOL . "=== TABELLE IN [$dbName] ===" . PHP_EOL;
        $conn2 = new PDO("sqlsrv:Server=192.168.1.34;Database=$dbName", '', '');
        $conn2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $tables = $conn2->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME");
        $allTables = [];
        while ($t = $tables->fetch(PDO::FETCH_ASSOC)) {
            $allTables[] = $t['TABLE_NAME'];
            echo "  " . $t['TABLE_NAME'] . PHP_EOL;
        }

        // Cerca tabelle con badge, anag, dip, card, timb, pers, utent nel nome
        $keywords = ['badge', 'anag', 'dip', 'card', 'timb', 'pers', 'utent'];
        $intTables = [];
        foreach ($allTables as $tbl) {
            foreach ($keywords as $kw) {
                if (stripos($tbl, $kw) !== false) {
                    $intTables[] = $tbl;
                    break;
                }
            }
        }

        if (!empty($intTables)) {
            echo PHP_EOL . "=== TABELLE INTERESSANTI ===" . PHP_EOL;
            foreach ($intTables as $tName) {
                echo PHP_EOL . "--- $tName ---" . PHP_EOL;
                $cols = $conn2->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='$tName' ORDER BY ORDINAL_POSITION");
                $colNames = [];
                while ($c = $cols->fetch(PDO::FETCH_ASSOC)) {
                    $colNames[] = $c['COLUMN_NAME'];
                }
                echo "Colonne: " . implode(', ', $colNames) . PHP_EOL;

                $rows = $conn2->query("SELECT TOP 5 * FROM [$tName]");
                $i = 0;
                while ($r = $rows->fetch(PDO::FETCH_ASSOC)) {
                    $vals = array_map(function($v) { return substr(trim((string)$v), 0, 30); }, $r);
                    echo implode(' | ', $vals) . PHP_EOL;
                    $i++;
                }
                if ($i === 0) echo "(vuota)" . PHP_EOL;
            }
        }
    }

} catch (Exception $e) {
    echo "Errore: " . $e->getMessage() . PHP_EOL;
    echo "Provo con istanza NETTIME..." . PHP_EOL;

    try {
        $conn = new PDO('sqlsrv:Server=192.168.1.34\NETTIME;Database=master', '', '');
        echo "Connesso a 192.168.1.34\\NETTIME!" . PHP_EOL;
        $dbs = $conn->query("SELECT name FROM sys.databases ORDER BY name");
        while ($row = $dbs->fetch(PDO::FETCH_ASSOC)) {
            echo "  " . $row['name'] . PHP_EOL;
        }
    } catch (Exception $e2) {
        echo "Anche NETTIME fallito: " . $e2->getMessage() . PHP_EOL;
        echo PHP_EOL . "Il SQL Server potrebbe richiedere autenticazione o un nome istanza diverso." . PHP_EOL;
    }
}
