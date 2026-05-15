<?php
/**
 * Analizza scheduler-task.log e raggruppa esecuzioni per comando.
 * Mostra ultime 10 esecuzioni di ogni cron.
 */

$logPath = __DIR__ . '/storage/logs/scheduler-task.log';
if (!file_exists($logPath)) {
    echo "Log non trovato: $logPath\n";
    exit(1);
}

// Leggi ultimi 500KB del log
$fh = fopen($logPath, 'r');
$size = filesize($logPath);
$offset = max(0, $size - 500000);
fseek($fh, $offset);
$content = fread($fh, $size - $offset);
fclose($fh);

$lines = explode("\n", $content);
$cmds = [];

// Parser: cerca pattern "YYYY-MM-DD HH:MM:SS Running [\"artisan\" <cmd> ...] ... <duration> <status>"
foreach ($lines as $line) {
    if (preg_match('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\s+Running\s+\["artisan"\s+([\w:-]+)/', $line, $m)) {
        $time = $m[1];
        $cmd = $m[2];
        $duration = '';
        $status = 'RUNNING';
        if (preg_match('/\s+(\d+m?\s?\d+s?|\d+ms|\d+s)\s+(DONE|FAILED)/', $line, $m2)) {
            $duration = $m2[1];
            $status = $m2[2];
        } elseif (preg_match('/\s+(FAILED|DONE)$/', $line, $m2)) {
            $status = $m2[1];
        }
        $cmds[$cmd][] = ['time' => $time, 'duration' => $duration, 'status' => $status];
    }
}

ksort($cmds);
echo "\n=== ULTIME ESECUZIONI PER COMANDO (log .scheduler-task.log) ===\n";
foreach ($cmds as $cmd => $execs) {
    $execs = array_slice(array_reverse($execs), 0, 8);
    echo "\n[$cmd] (" . count($execs) . " ultime)\n";
    foreach ($execs as $e) {
        echo "  {$e['time']}  {$e['duration']}  {$e['status']}\n";
    }
}

// Cerca anche errori/exception
echo "\n=== ERRORI in laravel.log (ultime 20 righe) ===\n";
$llog = __DIR__ . '/storage/logs/laravel.log';
if (file_exists($llog)) {
    $fh = fopen($llog, 'r');
    $size = filesize($llog);
    fseek($fh, max(0, $size - 50000));
    $tail = fread($fh, 50000);
    fclose($fh);
    $errLines = array_filter(explode("\n", $tail), fn($l) => preg_match('/ERROR|Exception|FAILED/i', $l));
    foreach (array_slice($errLines, -10) as $l) {
        echo "  " . substr(trim($l), 0, 200) . "\n";
    }
}
