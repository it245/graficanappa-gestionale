<?php
/**
 * Esplorazione completa NetTime sul .34
 * Cerca database, file, share, SQL Server — tutto il possibile
 * Eseguire sul server .60: php explore_nettime.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// ============================================================
// 1. SHARE ACCESSIBILI SUL .34
// ============================================================
echo "=== 1. SHARE ACCESSIBILI SUL .34 ===" . PHP_EOL;
// Prova net view per vedere le share disponibili
exec('net view \\\\192.168.1.34 2>&1', $output);
echo implode(PHP_EOL, $output) . PHP_EOL;

// ============================================================
// 2. PERCORSI NOTI SUL .34
// ============================================================
echo PHP_EOL . "=== 2. PERCORSI NOTI SUL .34 ===" . PHP_EOL;
$paths = [
    '\\\\192.168.1.34\\c$',
    '\\\\192.168.1.34\\c$\\NetTime',
    '\\\\192.168.1.34\\c$\\NetTime\\TIMBRA',
    '\\\\192.168.1.34\\c$\\NetTime\\DB',
    '\\\\192.168.1.34\\c$\\NetTime\\Data',
    '\\\\192.168.1.34\\c$\\NetTime\\Archivio',
    '\\\\192.168.1.34\\c$\\NetTime\\Export',
    '\\\\192.168.1.34\\c$\\NetTime\\Backup',
    '\\\\192.168.1.34\\c$\\Presenze',
    '\\\\192.168.1.34\\c$\\Presenze\\2026',
    '\\\\192.168.1.34\\NetTime',
    '\\\\192.168.1.34\\Presenze',
    '\\\\192.168.1.34\\timbrature',
    '\\\\192.168.1.34\\share',
    '\\\\192.168.1.34\\dati',
    '\\\\192.168.1.34\\TIMBRA',
];

foreach ($paths as $p) {
    if (is_dir($p)) {
        echo "  [OK] $p" . PHP_EOL;
        $files = @scandir($p);
        if ($files) {
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                $full = $p . '\\' . $f;
                $type = is_dir($full) ? '[DIR]' : filesize($full) . ' bytes';
                $mtime = is_file($full) ? date('Y-m-d H:i', filemtime($full)) : '';
                echo "    $f  ($type)  $mtime" . PHP_EOL;
            }
        }
    } else {
        echo "  [NO] $p" . PHP_EOL;
    }
}

// ============================================================
// 3. PERCORSI SUL .253 (share timbrature + altro)
// ============================================================
echo PHP_EOL . "=== 3. PERCORSI SUL .253 ===" . PHP_EOL;
$paths253 = [
    '\\\\192.168.1.253\\timbrature',
    '\\\\192.168.1.253\\c$',
    '\\\\192.168.1.253\\c$\\share_timbrature',
    '\\\\192.168.1.253\\c$\\NetTime',
    '\\\\192.168.1.253\\share',
];

foreach ($paths253 as $p) {
    if (is_dir($p)) {
        echo "  [OK] $p" . PHP_EOL;
        $files = @scandir($p);
        if ($files) {
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                $full = $p . '\\' . $f;
                $type = is_dir($full) ? '[DIR]' : filesize($full) . ' bytes';
                $mtime = is_file($full) ? date('Y-m-d H:i', filemtime($full)) : '';
                echo "    $f  ($type)  $mtime" . PHP_EOL;
            }
        }
    } else {
        echo "  [NO] $p" . PHP_EOL;
    }
}

// ============================================================
// 4. SQL SERVER SUL .34 (NetTime potrebbe usare DB)
// ============================================================
echo PHP_EOL . "=== 4. SQL SERVER SUL .34 ===" . PHP_EOL;

// Prova connessione con credenziali note
$servers = [
    ['host' => '192.168.1.34', 'user' => 'sa', 'pass' => 'softer'],
    ['host' => '192.168.1.34', 'user' => 'sa', 'pass' => 'sa'],
    ['host' => '192.168.1.34', 'user' => 'sa', 'pass' => ''],
    ['host' => '192.168.1.34\\SQLEXPRESS', 'user' => 'sa', 'pass' => 'softer'],
    ['host' => '192.168.1.34\\SQLEXPRESS', 'user' => 'sa', 'pass' => 'sa'],
    ['host' => '192.168.1.34,1433', 'user' => 'sa', 'pass' => 'softer'],
    ['host' => '192.168.1.34\\softer1', 'user' => 'sa', 'pass' => 'softer'],
];

$connOk = null;
foreach ($servers as $s) {
    echo "  Prova: {$s['host']} (user: {$s['user']}, pwd: {$s['pass']})... ";
    try {
        $conn = new PDO(
            "sqlsrv:Server={$s['host']};LoginTimeout=3",
            $s['user'],
            $s['pass']
        );
        echo "CONNESSO!" . PHP_EOL;
        $connOk = $conn;
        break;
    } catch (Exception $e) {
        $msg = $e->getMessage();
        // Abbrevia il messaggio
        if (strpos($msg, 'Login failed') !== false) echo "login fallito" . PHP_EOL;
        elseif (strpos($msg, 'Could not open') !== false || strpos($msg, 'TCP Provider') !== false) echo "non raggiungibile" . PHP_EOL;
        else echo substr($msg, 0, 80) . PHP_EOL;
    }
}

if ($connOk) {
    // Lista database
    echo PHP_EOL . "  DATABASE:" . PHP_EOL;
    $dbs = $connOk->query("SELECT name FROM sys.databases ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($dbs as $db) {
        echo "    $db" . PHP_EOL;
    }

    // Cerca tabelle con "timbr", "presenz", "badge", "dipendent", "anagraf" in tutti i DB
    echo PHP_EOL . "  TABELLE INTERESSANTI:" . PHP_EOL;
    foreach ($dbs as $db) {
        if (in_array($db, ['master', 'model', 'msdb', 'tempdb'])) continue;
        try {
            $tables = $connOk->query("SELECT TABLE_NAME FROM [$db].INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
            $interesting = array_filter($tables, function($t) {
                $t = strtolower($t);
                return strpos($t, 'timbr') !== false
                    || strpos($t, 'presenz') !== false
                    || strpos($t, 'badge') !== false
                    || strpos($t, 'dipend') !== false
                    || strpos($t, 'anagr') !== false
                    || strpos($t, 'person') !== false
                    || strpos($t, 'employee') !== false
                    || strpos($t, 'clock') !== false
                    || strpos($t, 'swipe') !== false
                    || strpos($t, 'access') !== false
                    || strpos($t, 'matric') !== false;
            });
            if (!empty($interesting)) {
                echo "    [$db]:" . PHP_EOL;
                foreach ($interesting as $t) {
                    $count = $connOk->query("SELECT COUNT(*) FROM [$db].dbo.[$t]")->fetchColumn();
                    echo "      $t ($count righe)" . PHP_EOL;
                }
            }
            // Se poche tabelle, mostra tutte
            if (count($tables) <= 30 && count($tables) > 0 && empty($interesting)) {
                echo "    [$db] (" . count($tables) . " tabelle): " . implode(', ', array_slice($tables, 0, 15)) . PHP_EOL;
            }
        } catch (Exception $e) {
            // skip
        }
    }

    // Cerca TUTTE le tabelle in tutti i DB non-system
    echo PHP_EOL . "  TUTTE LE TABELLE PER DB:" . PHP_EOL;
    foreach ($dbs as $db) {
        if (in_array($db, ['master', 'model', 'msdb', 'tempdb'])) continue;
        try {
            $tables = $connOk->query("SELECT TABLE_NAME FROM [$db].INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
            echo "    [$db] (" . count($tables) . " tabelle):" . PHP_EOL;
            foreach ($tables as $t) {
                echo "      $t" . PHP_EOL;
            }
        } catch (Exception $e) {
            echo "    [$db]: errore accesso" . PHP_EOL;
        }
    }
}

// ============================================================
// 5. PORTE APERTE SUL .34 (SQL Server, HTTP, ecc)
// ============================================================
echo PHP_EOL . "=== 5. PORTE COMUNI SUL .34 ===" . PHP_EOL;
$ports = [80, 443, 1433, 3306, 5432, 8080, 8443, 445, 139, 3389];
foreach ($ports as $port) {
    $fp = @fsockopen('192.168.1.34', $port, $errno, $errstr, 1);
    if ($fp) {
        echo "  Porta $port: APERTA" . PHP_EOL;
        fclose($fp);
    } else {
        echo "  Porta $port: chiusa" . PHP_EOL;
    }
}

echo PHP_EOL . "=== FINE ESPLORAZIONE ===" . PHP_EOL;
