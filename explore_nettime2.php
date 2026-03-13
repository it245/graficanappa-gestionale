<?php
/**
 * Confronta i file timbrature sul .253 e cerca dati mancanti
 * Eseguire sul server: php explore_nettime2.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// File 1: quello nella share (copiato dal task ogni minuto)
$file1 = '\\\\192.168.1.253\\timbrature\\timbrature.txt';
// File 2: quello nella root C:\ del .253
$file2 = '\\\\192.168.1.253\\c$\\timbrature_nettime.txt';

foreach ([$file1, $file2] as $path) {
    echo "=== $path ===" . PHP_EOL;
    if (!file_exists($path)) {
        echo "  NON trovato" . PHP_EOL . PHP_EOL;
        continue;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    echo "  Righe totali: " . count($lines) . PHP_EOL;
    echo "  Size: " . filesize($path) . " bytes" . PHP_EOL;
    echo "  Ultima modifica: " . date('Y-m-d H:i:s', filemtime($path)) . PHP_EOL;

    // Conta per giorno (ultimi 10 giorni)
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

    echo "  Righe per giorno (ultimi 10):" . PHP_EOL;
    $i = 0;
    foreach ($perGiorno as $data => $count) {
        if ($i++ >= 10) break;
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
    echo PHP_EOL;
}

// Esplora altre cartelle .253 che potrebbero avere dati NetTime
echo "=== CARTELLE INTERESSANTI SUL .253 ===" . PHP_EOL;
$explore = [
    '\\\\192.168.1.253\\c$\\GN',
    '\\\\192.168.1.253\\c$\\GraficheNappa',
    '\\\\192.168.1.253\\c$\\prev 2024',
    '\\\\192.168.1.253\\c$\\TEMP',
    '\\\\192.168.1.253\\c$\\Appoggio Interlem',
    '\\\\192.168.1.253\\c$\\Omega',
];

foreach ($explore as $dir) {
    if (!is_dir($dir)) {
        echo "  [NO] $dir" . PHP_EOL;
        continue;
    }
    echo "  [OK] $dir" . PHP_EOL;
    $files = @scandir($dir);
    if ($files) {
        $count = 0;
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            $full = $dir . '\\' . $f;
            $type = is_dir($full) ? '[DIR]' : filesize($full) . 'b';
            echo "    $f  ($type)" . PHP_EOL;
            if (++$count >= 30) { echo "    ... (troncato)" . PHP_EOL; break; }
        }
    }
}

// Cerca file con "timbr" o "presenz" o "nettime" nel nome su C:\ del .253
echo PHP_EOL . "=== FILE CON TIMBR/PRESENZ/NETTIME SU C:\\ .253 ===" . PHP_EOL;
$root = '\\\\192.168.1.253\\c$';
$files = @scandir($root);
if ($files) {
    foreach ($files as $f) {
        $fl = strtolower($f);
        if (strpos($fl, 'timbr') !== false || strpos($fl, 'presenz') !== false || strpos($fl, 'nettime') !== false || strpos($fl, 'badge') !== false) {
            $full = $root . '\\' . $f;
            $type = is_dir($full) ? '[DIR]' : filesize($full) . ' bytes';
            $mtime = is_file($full) ? date('Y-m-d H:i', filemtime($full)) : '';
            echo "  $f  ($type)  $mtime" . PHP_EOL;
        }
    }
}

// Prova a connettersi al .34 via porta 80 (IIS/NetTime web)
echo PHP_EOL . "=== HTTP .34 (porta 80) ===" . PHP_EOL;
$ctx = stream_context_create(['http' => ['timeout' => 3]]);
$html = @file_get_contents('http://192.168.1.34/', false, $ctx);
if ($html) {
    echo "  Risposta HTTP: " . strlen($html) . " bytes" . PHP_EOL;
    echo "  Primi 300 char: " . substr(strip_tags($html), 0, 300) . PHP_EOL;
} else {
    echo "  Nessuna risposta HTTP" . PHP_EOL;
}
