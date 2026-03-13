<?php
/**
 * Esplora le share NetTime e Presenze sul .34
 * Eseguire sul server .60: php explore_nettime3.php
 */

echo "=== 1. CONNESSIONE SHARE .34 ===" . PHP_EOL;
exec('net use \\\\192.168.1.34\\NetTime 2>&1', $out1);
echo "NetTime: " . implode(' ', $out1) . PHP_EOL;
exec('net use \\\\192.168.1.34\\Presenze 2>&1', $out2);
echo "Presenze: " . implode(' ', $out2) . PHP_EOL;

// ============================================================
echo PHP_EOL . "=== 2. CARTELLA TIMBRA ===" . PHP_EOL;
$timbra = '\\\\192.168.1.34\\NetTime\\TIMBRA';
if (is_dir($timbra)) {
    $files = scandir($timbra);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $full = $timbra . '\\' . $f;
        $type = is_dir($full) ? '[DIR]' : number_format(filesize($full)) . ' bytes';
        $mtime = is_file($full) ? date('Y-m-d H:i', filemtime($full)) : '';
        echo "  $f  ($type)  $mtime" . PHP_EOL;
    }
} else {
    echo "  NON accessibile" . PHP_EOL;
}

// ============================================================
echo PHP_EOL . "=== 3. TIMBRACP.BKP LIVE - righe per giorno ===" . PHP_EOL;
$bkp = '\\\\192.168.1.34\\NetTime\\TIMBRA\\TIMBRACP.BKP';
if (file_exists($bkp)) {
    $lines = file($bkp, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    echo "  Righe totali: " . count($lines) . PHP_EOL;
    echo "  Size: " . number_format(filesize($bkp)) . " bytes" . PHP_EOL;

    $perGiorno = [];
    foreach ($lines as $line) {
        if (preg_match('/^R?\d{9}\s+\d{8}\s+(\d{6})\s/', $line, $m)) {
            $perGiorno[$m[1]] = ($perGiorno[$m[1]] ?? 0) + 1;
        }
    }
    uksort($perGiorno, function($a, $b) {
        $da = substr($a,4,2).substr($a,2,2).substr($a,0,2);
        $db = substr($b,4,2).substr($b,2,2).substr($b,0,2);
        return strcmp($db, $da);
    });
    echo "  Ultimi 14 giorni:" . PHP_EOL;
    $i = 0;
    foreach ($perGiorno as $data => $count) {
        if ($i++ >= 14) break;
        $giorno = substr($data,0,2).'/'.substr($data,2,2).'/20'.substr($data,4,2);
        echo "    $giorno: $count righe" . PHP_EOL;
    }

    // Dettaglio 12/03
    echo "  Dettaglio 12/03:" . PHP_EOL;
    foreach ($lines as $line) {
        if (preg_match('/^R?\d{9}\s+\d{8}\s+120326\s/', $line)) {
            echo "    $line" . PHP_EOL;
        }
    }
} else {
    echo "  File NON trovato: $bkp" . PHP_EOL;
}

// ============================================================
echo PHP_EOL . "=== 4. ALTRE CARTELLE NETTIME ===" . PHP_EOL;
$dirs = ['DAT', 'DBSTANDARD', 'Evolution', 'OUTPUT', 'CONTROL', 'SYNC_FTP', 'TIMESHEET'];
foreach ($dirs as $d) {
    $path = '\\\\192.168.1.34\\NetTime\\' . $d;
    if (!is_dir($path)) {
        echo "  [$d] NON accessibile" . PHP_EOL;
        continue;
    }
    echo "  [$d]:" . PHP_EOL;
    $files = scandir($path);
    $count = 0;
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $full = $path . '\\' . $f;
        $type = is_dir($full) ? '[DIR]' : number_format(filesize($full)) . 'b';
        $mtime = is_file($full) ? date('Y-m-d H:i', filemtime($full)) : '';
        echo "    $f  ($type)  $mtime" . PHP_EOL;
        // Se è una sottocartella, mostra anche il contenuto
        if (is_dir($full)) {
            $sub = @scandir($full);
            if ($sub) {
                $sc = 0;
                foreach ($sub as $sf) {
                    if ($sf === '.' || $sf === '..') continue;
                    $sfull = $full . '\\' . $sf;
                    $st = is_dir($sfull) ? '[DIR]' : number_format(filesize($sfull)) . 'b';
                    echo "      $sf  ($st)" . PHP_EOL;
                    if (++$sc >= 10) { echo "      ..." . PHP_EOL; break; }
                }
            }
        }
        if (++$count >= 20) { echo "    ... (troncato)" . PHP_EOL; break; }
    }
}

// ============================================================
echo PHP_EOL . "=== 5. PRESENZE - FILE PIU RECENTI ===" . PHP_EOL;
$presenze = '\\\\192.168.1.34\\Presenze';
if (is_dir($presenze)) {
    $files = scandir($presenze);
    // Ordina per data modifica (piu recente prima)
    $fileInfo = [];
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $full = $presenze . '\\' . $f;
        if (is_file($full)) {
            $fileInfo[] = ['name' => $f, 'size' => filesize($full), 'mtime' => filemtime($full)];
        }
    }
    usort($fileInfo, fn($a, $b) => $b['mtime'] - $a['mtime']);
    echo "  Ultimi 5 file:" . PHP_EOL;
    foreach (array_slice($fileInfo, 0, 5) as $fi) {
        echo "    {$fi['name']}  ({$fi['size']} bytes)  " . date('Y-m-d H:i', $fi['mtime']) . PHP_EOL;
    }

    // Leggi il piu recente e cerca matricole 600+
    if (!empty($fileInfo)) {
        $latest = $presenze . '\\' . $fileInfo[0]['name'];
        $content = file_get_contents($latest);
        echo PHP_EOL . "  File piu recente: {$fileInfo[0]['name']}" . PHP_EOL;
        echo "  Contiene matricole 0006xx? ";
        if (preg_match_all('/(\d{6})/', $content, $allMatr)) {
            $m600 = array_filter($allMatr[1], fn($m) => intval($m) >= 600 && intval($m) <= 700);
            echo count($m600) > 0 ? "SI: " . implode(', ', array_unique($m600)) : "NO";
        }
        echo PHP_EOL;
    }
} else {
    echo "  NON accessibile" . PHP_EOL;
}

echo PHP_EOL . "=== FINE ===" . PHP_EOL;
