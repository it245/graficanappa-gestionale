<?php
/**
 * Cerca file presenze aggiornati sul .34 e .253
 * Eseguire sul server: php find_presenze.php
 */

$paths = [
    '\\\\192.168.1.34\\c$\\Presenze',
    '\\\\192.168.1.34\\c$\\NetTime\\TIMBRA',
    '\\\\192.168.1.34\\c$\\NetTime',
    '\\\\192.168.1.253\\timbrature',
];

foreach ($paths as $dir) {
    echo "=== $dir ===" . PHP_EOL;
    if (!is_dir($dir)) {
        echo "  NON accessibile" . PHP_EOL . PHP_EOL;
        continue;
    }

    $files = scandir($dir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        $full = $dir . '\\' . $f;
        $size = is_file($full) ? filesize($full) : '[DIR]';
        $mtime = is_file($full) ? date('Y-m-d H:i', filemtime($full)) : '';
        echo "  $f  ($size bytes)  $mtime" . PHP_EOL;
    }
    echo PHP_EOL;
}

// Cerca file presenze recenti (marzo 2026)
$mesi = ['MARZO 2026', 'FEBBRAIO 2026', 'GENNAIO 2026', 'MARZO 26', 'MAR 2026', '032026', '03 2026'];
echo "=== CERCA FILE PRESENZE RECENTI ===" . PHP_EOL;
$presenzeDir = '\\\\192.168.1.34\\c$\\Presenze';
if (is_dir($presenzeDir)) {
    $files = scandir($presenzeDir);
    foreach ($files as $f) {
        $upper = strtoupper($f);
        if (strpos($upper, 'PRESENZ') !== false || strpos($upper, '2026') !== false || strpos($upper, '2025') !== false) {
            $full = $presenzeDir . '\\' . $f;
            $size = is_file($full) ? filesize($full) : '[DIR]';
            $mtime = is_file($full) ? date('Y-m-d H:i', filemtime($full)) : '';
            echo "  $f  ($size bytes)  $mtime" . PHP_EOL;
        }
    }
} else {
    echo "  Cartella Presenze non accessibile" . PHP_EOL;
}

// Prova anche a leggere il file timbrature live dal .34
$livePath = '\\\\192.168.1.34\\c$\\NetTime\\TIMBRA\\TIMBRACP.BKP';
if (file_exists($livePath)) {
    $content = file_get_contents($livePath);
    $lines = explode("\n", trim($content));
    echo PHP_EOL . "=== TIMBRACP.BKP LIVE (.34) ===" . PHP_EOL;
    echo "  Righe totali: " . count($lines) . PHP_EOL;

    // Estrai matricole uniche
    $matricole = [];
    foreach ($lines as $line) {
        if (preg_match('/^R?\d{9}\s+(\d{8})\s/', $line, $m)) {
            $matr = str_pad(ltrim($m[1], '0'), 6, '0', STR_PAD_LEFT);
            $matricole[$matr] = true;
        }
    }
    echo "  Matricole uniche: " . count($matricole) . PHP_EOL;
    echo "  Lista: " . implode(', ', array_keys($matricole)) . PHP_EOL;
} else {
    echo PHP_EOL . "TIMBRACP.BKP live NON accessibile: $livePath" . PHP_EOL;
}
