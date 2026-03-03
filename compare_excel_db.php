<?php
/**
 * Confronta l'Excel condiviso con il DB per trovare le differenze.
 * Uso: php compare_excel_db.php [percorso_excel]
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use PhpOffice\PhpSpreadsheet\IOFactory;

$excelPath = $argv[1] ?? 'C:\\Users\\Giovanni\\Desktop\\dashboard_mes.xlsx';

if (!file_exists($excelPath)) {
    echo "File non trovato: $excelPath\n";
    exit(1);
}

echo "=== Confronto Excel vs DB ===\n";
echo "File: $excelPath\n";
echo "Modificato: " . date('d/m/Y H:i:s', filemtime($excelPath)) . "\n\n";

$spreadsheet = IOFactory::load($excelPath);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray(null, false, false, true);

$differenze = [];
$isFirst = true;

foreach ($rows as $row) {
    if ($isFirst) { $isFirst = false; continue; }

    $id = $row['A'] ?? null;
    if (!$id || !is_numeric($id)) continue;

    $fase = OrdineFase::with('ordine', 'faseCatalogo')->find((int) $id);
    if (!$fase) continue;

    $commessa = $fase->ordine->commessa ?? '?';
    $nomeFase = $fase->faseCatalogo->nome ?? $fase->fase;

    $diffs = [];

    // Stato (colonna C)
    $excelStato = trim($row['C'] ?? '');
    if ($excelStato !== '' && is_numeric($excelStato) && (int)$excelStato !== (int)$fase->stato) {
        $diffs[] = "stato: Excel={$excelStato} DB={$fase->stato}";
    }

    // Qta Prod (colonna V)
    $excelQtaProd = trim($row['V'] ?? '');
    if ($excelQtaProd !== '' && $excelQtaProd !== '-') {
        $dbQta = (float)($fase->qta_prod ?? 0);
        $exQta = (float)str_replace(',', '.', $excelQtaProd);
        if (abs($exQta - $dbQta) > 0.001) {
            $diffs[] = "qta_prod: Excel={$excelQtaProd} DB={$dbQta}";
        }
    }

    // Priorita (colonna I)
    $excelPrio = trim($row['I'] ?? '');
    if ($excelPrio !== '' && $excelPrio !== '-') {
        $dbPrio = (float)($fase->priorita ?? 0);
        $exPrio = (float)str_replace(',', '.', $excelPrio);
        if (abs($exPrio - $dbPrio) > 0.001) {
            $diffs[] = "priorita: Excel={$excelPrio} DB={$dbPrio}";
        }
    }

    // Note (colonna W)
    $excelNote = trim($row['W'] ?? '');
    $dbNote = trim($fase->note ?? '');
    if ($excelNote !== '-' && $excelNote !== $dbNote && !($excelNote === '' && $dbNote === '')) {
        $diffs[] = "note: Excel=\"{$excelNote}\" DB=\"{$dbNote}\"";
    }

    // Data consegna (colonna K)
    $excelConsegna = trim($row['K'] ?? '');
    $dbConsegna = $fase->ordine->data_prevista_consegna ?? '';
    if ($excelConsegna !== '' && $excelConsegna !== '-' && $dbConsegna) {
        // Normalizza formato
        $dbFmt = \Carbon\Carbon::parse($dbConsegna)->format('d/m/Y');
        if (is_numeric($excelConsegna) && (float)$excelConsegna > 25000) {
            $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$excelConsegna);
            $excelConsegna = $excelDate->format('d/m/Y');
        }
        if ($excelConsegna !== $dbFmt) {
            $diffs[] = "consegna: Excel={$excelConsegna} DB={$dbFmt}";
        }
    }

    if (!empty($diffs)) {
        $differenze[] = [
            'id' => $id,
            'commessa' => $commessa,
            'fase' => $nomeFase,
            'diffs' => $diffs,
        ];
    }
}

if (empty($differenze)) {
    echo "Nessuna differenza - Excel e DB sono allineati.\n";
} else {
    echo sprintf("%-8s %-16s %-22s %s\n", 'ID', 'Commessa', 'Fase', 'Differenze');
    echo str_repeat('-', 100) . "\n";
    foreach ($differenze as $d) {
        foreach ($d['diffs'] as $i => $diff) {
            if ($i === 0) {
                echo sprintf("%-8s %-16s %-22s %s\n", $d['id'], $d['commessa'], $d['fase'], $diff);
            } else {
                echo sprintf("%-8s %-16s %-22s %s\n", '', '', '', $diff);
            }
        }
    }
    echo "\nTotale: " . count($differenze) . " fasi con differenze.\n";
}
