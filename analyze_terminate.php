<?php
error_reporting(0);
require __DIR__ . '/vendor/autoload.php';

$file = 'C:\Users\Giovanni\Desktop\dashboard_mes.xlsx';
if (!file_exists($file)) { fwrite(STDERR, "FILE NOT FOUND\n"); exit(1); }

ini_set('memory_limit', '2G');
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
$reader->setReadDataOnly(true);
$ss = $reader->load($file);
$sheet = $ss->getSheetByName('Terminate');
if (!$sheet) { fwrite(STDERR, "NO Terminate sheet\n"); exit(1); }

$rows = $sheet->toArray(null, true, true, false);
array_shift($rows);

$parseHM = function ($v) {
    if (empty($v)) return null;
    if (is_numeric($v)) return (float) $v * 3600;
    if (preg_match('/(?:(\d+)h\s*)?(\d+)m/i', (string) $v, $m)) {
        return ((int) ($m[1] ?? 0)) * 3600 + ((int) $m[2]) * 60;
    }
    return null;
};

// Filtri: solo stampa offset, aprile 2026
$delta = [];
$conPrev = $conLav = $conEntrambi = 0;
$sommaPrev = $sommaLav = 0;
$totFasi = 0;
foreach ($rows as $r) {
    $reparto = strtolower((string) ($r[19] ?? ''));
    if (stripos($reparto, 'stampa offset') === false) continue;
    $dataReg = (string) ($r[9] ?? '');
    // Formato Excel: dd/mm/YYYY → filtro aprile 2026
    if (! preg_match('#04/2026#', $dataReg)) continue;

    $totFasi++;
    $secPrev = $parseHM($r[33] ?? '');
    $secLav  = $parseHM($r[34] ?? '');
    if ($secPrev !== null) { $conPrev++; $sommaPrev += $secPrev; }
    if ($secLav !== null) { $conLav++; $sommaLav += $secLav; }
    if ($secPrev !== null && $secLav !== null && $secPrev > 60) {
        $conEntrambi++;
        $delta[] = [
            'c'   => $r[1],
            'f'   => $r[18],
            'rep' => $r[19],
            'dt'  => $dataReg,
            'p'   => $secPrev,
            'l'   => $secLav,
            'd'   => ($secLav - $secPrev) / $secPrev * 100,
        ];
    }
}

echo "Totale fasi Terminate: " . count($rows) . "\n";
echo "Con Ore Prev: $conPrev\n";
echo "Con Ore Lav: $conLav\n";
echo "Con entrambi (e prev>60s): $conEntrambi\n";
echo sprintf("Somma Ore Prev: %.1fh / Ore Lav: %.1fh / Δ globale: %+.1f%%\n", $sommaPrev / 3600, $sommaLav / 3600, $sommaPrev > 0 ? ($sommaLav - $sommaPrev) / $sommaPrev * 100 : 0);

usort($delta, fn ($a, $b) => $b['d'] <=> $a['d']);
echo "\n=== TOP 15 SFORATE (lavorato >> previsto) ===\n";
foreach (array_slice($delta, 0, 15) as $d) {
    echo sprintf("  %s %-22s [%s] prev=%.2fh lav=%.2fh Δ%+.0f%%\n", $d['c'], $d['f'], $d['rep'], $d['p'] / 3600, $d['l'] / 3600, $d['d']);
}

usort($delta, fn ($a, $b) => $a['d'] <=> $b['d']);
echo "\n=== TOP 15 SOTTOSTIMATE (lavorato << previsto) ===\n";
foreach (array_slice($delta, 0, 15) as $d) {
    echo sprintf("  %s %-22s [%s] prev=%.2fh lav=%.2fh Δ%+.0f%%\n", $d['c'], $d['f'], $d['rep'], $d['p'] / 3600, $d['l'] / 3600, $d['d']);
}

$buckets = ['<-50%' => 0, '-50..-20%' => 0, '-20..+20%' => 0, '+20..+50%' => 0, '+50..+100%' => 0, '>+100%' => 0];
foreach ($delta as $d) {
    $p = $d['d'];
    if ($p < -50) $buckets['<-50%']++;
    elseif ($p < -20) $buckets['-50..-20%']++;
    elseif ($p <= 20) $buckets['-20..+20%']++;
    elseif ($p <= 50) $buckets['+20..+50%']++;
    elseif ($p <= 100) $buckets['+50..+100%']++;
    else $buckets['>+100%']++;
}
echo "\n=== DISTRIBUZIONE SCOSTAMENTO % ===\n";
foreach ($buckets as $k => $v) {
    echo sprintf("  %-12s %5d (%5.1f%%)\n", $k, $v, $conEntrambi > 0 ? $v / $conEntrambi * 100 : 0);
}
