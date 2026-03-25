<?php
// Legge direttamente il file NetTime e cerca le timbrature di un dipendente per una data

$paths = [
    '\\\\192.168.1.34\\NetTime\\TIMBRA\\TIMBRACP.BKP',
    '\\\\192.168.1.253\\timbrature\\timbrature.txt',
];

$matricola = $argv[1] ?? '000666'; // TORROMACCO
$data = $argv[2] ?? '2026-03-23';
// Formato file NetTime: DDMMYY (es. 230326 = 23/03/26)
$parts = explode('-', $data); // 2026-03-23
$dataCerca = $parts[2] . $parts[1] . substr($parts[0], 2); // 230326

echo "=== RICERCA NEL FILE NETTIME ===" . PHP_EOL;
echo "Matricola: {$matricola} | Data: {$data} ({$dataCerca})" . PHP_EOL . PHP_EOL;

foreach ($paths as $path) {
    echo "--- File: {$path} ---" . PHP_EOL;

    if (!file_exists($path)) {
        echo "  Non accessibile" . PHP_EOL . PHP_EOL;
        // Prova connessione
        if (str_contains($path, '192.168.1.34')) {
            @exec('net use \\\\192.168.1.34\\NetTime /user:mes M3s@Nappa26 2>&1', $out);
            echo "  Tentativo connessione: " . implode(' ', $out) . PHP_EOL;
            if (file_exists($path)) {
                echo "  Ora accessibile!" . PHP_EOL;
            } else {
                echo "  Ancora non accessibile." . PHP_EOL . PHP_EOL;
                continue;
            }
        } else {
            continue;
        }
    }

    $content = file_get_contents($path);
    if (!$content) {
        echo "  File vuoto o errore lettura" . PHP_EOL . PHP_EOL;
        continue;
    }

    $lines = explode("\n", $content);
    echo "  Righe totali: " . count($lines) . PHP_EOL;

    $trovate = 0;
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        // Cerca la matricola nella riga
        if (str_contains($line, $matricola)) {
            // Cerca anche la data
            if (str_contains($line, $dataCerca) || str_contains($line, $data)) {
                echo "  TROVATA: {$line}" . PHP_EOL;
                $trovate++;
            }
        }
    }

    if ($trovate === 0) {
        // Cerca solo per matricola (tutte le date) per capire il formato
        $tutteMatricola = 0;
        $esempioRiga = '';
        foreach ($lines as $line) {
            if (str_contains($line, $matricola)) {
                $tutteMatricola++;
                if (!$esempioRiga) $esempioRiga = trim($line);
            }
        }
        echo "  Nessuna per data {$dataCerca}" . PHP_EOL;
        echo "  Totale righe matricola {$matricola}: {$tutteMatricola}" . PHP_EOL;
        if ($esempioRiga) echo "  Esempio riga: {$esempioRiga}" . PHP_EOL;
    }

    echo PHP_EOL;
}

echo "DONE" . PHP_EOL;
