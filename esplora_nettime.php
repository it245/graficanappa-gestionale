<?php
// Esplora tutto il contenuto di NetTime sul .34

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$user = env('NETTIME_SHARE_USER', 'mes');
$pass = env('NETTIME_SHARE_PASS', '');
@exec("net use \\\\192.168.1.34\\NetTime /user:{$user} {$pass} 2>&1");

$basePath = '\\\\192.168.1.34\\NetTime';
$matricola = '000666'; // TORROMACCO
$dataCerca = '230326'; // 23/03/2026

echo "=== ESPLORAZIONE COMPLETA NETTIME (.34) ===" . PHP_EOL;
echo "Matricola: {$matricola} | Data: {$dataCerca}" . PHP_EOL . PHP_EOL;

// 1. Lista cartelle
echo "--- 1. CARTELLE IN {$basePath} ---" . PHP_EOL;
if (is_dir($basePath)) {
    $items = scandir($basePath);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $fullPath = $basePath . '\\' . $item;
        $tipo = is_dir($fullPath) ? '[DIR]' : '[FILE] ' . filesize($fullPath) . ' byte';
        echo "  {$tipo} {$item}" . PHP_EOL;
    }
} else {
    echo "  Non accessibile!" . PHP_EOL;
    exit;
}

// 2. Esplora sottocartelle
echo PHP_EOL . "--- 2. SOTTOCARTELLE ---" . PHP_EOL;
$dirs = ['TIMBRA', 'Dati', 'Data', 'DB', 'Archivio', 'Archive', 'Backup', 'Log', 'Logs'];
foreach ($dirs as $dir) {
    $dirPath = $basePath . '\\' . $dir;
    if (!is_dir($dirPath)) continue;

    echo PHP_EOL . "  [{$dir}]:" . PHP_EOL;
    $subItems = scandir($dirPath);
    foreach ($subItems as $si) {
        if ($si === '.' || $si === '..') continue;
        $fp = $dirPath . '\\' . $si;
        if (is_dir($fp)) {
            echo "    [DIR] {$si}" . PHP_EOL;
            // Esplora anche questa
            $subSub = scandir($fp);
            foreach ($subSub as $ss) {
                if ($ss === '.' || $ss === '..') continue;
                $ssp = $fp . '\\' . $ss;
                $tipo = is_dir($ssp) ? '[DIR]' : '[FILE] ' . filesize($ssp) . 'b';
                echo "      {$tipo} {$ss}" . PHP_EOL;
            }
        } else {
            $size = filesize($fp);
            echo "    [FILE] {$si} ({$size} byte)" . PHP_EOL;
        }
    }
}

// 3. Cerca in TUTTI i file la matricola + data
echo PHP_EOL . "--- 3. RICERCA MATRICOLA {$matricola} + DATA {$dataCerca} IN TUTTI I FILE ---" . PHP_EOL;

function searchFiles($dir, $matricola, $dataCerca, $depth = 0) {
    if ($depth > 3) return;
    if (!is_dir($dir)) return;

    $items = @scandir($dir);
    if (!$items) return;

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '\\' . $item;

        if (is_dir($path)) {
            searchFiles($path, $matricola, $dataCerca, $depth + 1);
        } elseif (is_file($path)) {
            $size = filesize($path);
            if ($size > 50000000 || $size == 0) continue; // skip > 50MB o vuoti

            $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            // Cerca solo in file testo
            if (in_array($ext, ['bkp', 'txt', 'csv', 'dat', 'log', '']) || !$ext) {
                $content = @file_get_contents($path);
                if (!$content) continue;

                // Cerca matricola + data nella stessa riga
                $lines = explode("\n", $content);
                $found = false;
                foreach ($lines as $line) {
                    if (strpos($line, $matricola) !== false && strpos($line, $dataCerca) !== false) {
                        if (!$found) {
                            echo PHP_EOL . "  FILE: {$path}" . PHP_EOL;
                            $found = true;
                        }
                        echo "    " . trim($line) . PHP_EOL;
                    }
                }

                // Cerca anche con formato diverso (23-03-26, 23/03/26, 2026-03-23)
                if (!$found) {
                    foreach ($lines as $line) {
                        if (strpos($line, $matricola) !== false &&
                            (strpos($line, '23/03') !== false || strpos($line, '23-03') !== false || strpos($line, '2026-03-23') !== false)) {
                            if (!$found) {
                                echo PHP_EOL . "  FILE: {$path}" . PHP_EOL;
                                $found = true;
                            }
                            echo "    " . trim($line) . PHP_EOL;
                        }
                    }
                }
            }
        }
    }
}

searchFiles($basePath, $matricola, $dataCerca);

// 4. Cerca anche con 666 (senza zeri)
echo PHP_EOL . "--- 4. RICERCA CON '666' + '0741' (l'ora mancante) ---" . PHP_EOL;
$timbraFile = $basePath . '\\TIMBRA\\TIMBRACP.BKP';
if (file_exists($timbraFile)) {
    $content = file_get_contents($timbraFile);
    $lines = explode("\n", $content);
    foreach ($lines as $line) {
        // Cerca la combinazione 666 + 0741 (l'entrata mancante alle 7:41)
        if (strpos($line, '666') !== false && strpos($line, '0741') !== false) {
            echo "  {$line}" . PHP_EOL;
        }
    }

    // Cerca anche tutte le timbrature delle 07:41 del 23/03
    echo PHP_EOL . "--- 5. TUTTE LE TIMBRATURE ALLE 07:41 DEL 23/03 ---" . PHP_EOL;
    foreach ($lines as $line) {
        if (strpos($line, $dataCerca) !== false && strpos($line, '0741') !== false) {
            echo "  {$line}" . PHP_EOL;
        }
    }
}

echo PHP_EOL . "DONE" . PHP_EOL;
