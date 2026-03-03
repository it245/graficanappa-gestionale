<?php
/**
 * Confronta l'Excel condiviso con il DB per trovare le differenze.
 * Controlla TUTTE le colonne A-AG.
 * Uso: php compare_excel_db.php [percorso_excel]
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\OrdineFase;
use App\Helpers\DescrizioneParser;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

$excelPath = $argv[1] ?? 'C:\\Users\\Giovanni\\Desktop\\dashboard_mes.xlsx';

if (!file_exists($excelPath)) {
    echo "File non trovato: $excelPath\n";
    exit(1);
}

echo "=== Confronto Excel vs DB (tutte le colonne A-AG) ===\n";
echo "File: $excelPath\n";
echo "Modificato: " . date('d/m/Y H:i:s', filemtime($excelPath)) . "\n\n";

$spreadsheet = IOFactory::load($excelPath);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray(null, false, false, true);

$differenze = [];
$righeExcel = 0;
$righeDb = 0;
$isFirst = true;

// Helper: normalizza valore per confronto
function norm($val) {
    $v = trim((string)($val ?? ''));
    return ($v === '-' || $v === '') ? '' : $v;
}
function normNum($val) {
    $v = norm($val);
    if ($v === '') return '';
    return (string)(float)str_replace(',', '.', $v);
}
function normDate($val) {
    $v = norm($val);
    if ($v === '') return '';
    if (is_numeric($v) && (float)$v > 25000) {
        return ExcelDate::excelToDateTimeObject((float)$v)->format('d/m/Y');
    }
    return $v;
}
function normDateTime($val) {
    $v = norm($val);
    if ($v === '') return '';
    if (is_numeric($v) && (float)$v > 25000) {
        return ExcelDate::excelToDateTimeObject((float)$v)->format('d/m/Y H:i:s');
    }
    return $v;
}

foreach ($rows as $row) {
    if ($isFirst) { $isFirst = false; continue; }

    $id = $row['A'] ?? null;
    if (!$id || !is_numeric($id)) continue;
    $righeExcel++;

    $fase = OrdineFase::with(['ordine', 'faseCatalogo.reparto', 'operatori' => fn($q) => $q->select('operatori.id', 'nome')])->find((int) $id);
    if (!$fase) {
        $differenze[] = ['id' => $id, 'commessa' => '?', 'fase' => '?', 'diffs' => ['Fase non trovata nel DB (eliminata?)']];
        continue;
    }
    $righeDb++;

    $ordine = $fase->ordine;
    $commessa = $ordine->commessa ?? '?';
    $nomeFase = $fase->faseCatalogo->nome ?? $fase->fase;
    $repartoNome = $fase->faseCatalogo->reparto->nome ?? '';

    // Ricostruisci i valori DB come li scrive l'export
    $dataInizio = null;
    if ($fase->operatori->isNotEmpty()) {
        $primo = $fase->operatori->sortBy('pivot.data_inizio')->first();
        $dataInizio = $primo?->pivot->data_inizio;
    }
    if (!$dataInizio) $dataInizio = $fase->getAttributes()['data_inizio'] ?? null;
    $dataInizio = $dataInizio ? Carbon::parse($dataInizio)->format('d/m/Y H:i:s') : '';

    $dataFine = $fase->getAttributes()['data_fine'] ?? null;
    $dataFine = $dataFine ? Carbon::parse($dataFine)->format('d/m/Y H:i:s') : '';

    $operatoriNomi = $fase->operatori->pluck('nome')->implode(', ');

    // Mappa colonne: lettera => [label, valore_db]
    $dbValues = [
        'B'  => ['Commessa',       $ordine->commessa ?? ''],
        'C'  => ['Stato',          (string)($fase->stato ?? '')],
        'D'  => ['Cliente',        $ordine->cliente_nome ?? ''],
        'E'  => ['Cod Articolo',   $ordine->cod_art ?? ''],
        'F'  => ['Descrizione',    $ordine->descrizione ?? ''],
        'G'  => ['Qta',            (string)($ordine->qta_richiesta ?? '')],
        'H'  => ['UM',             $ordine->um ?? ''],
        'I'  => ['Priorita',       (string)($fase->priorita ?? '')],
        'J'  => ['Data Reg.',      $ordine->data_registrazione ? Carbon::parse($ordine->data_registrazione)->format('d/m/Y') : ''],
        'K'  => ['Data Consegna',  $ordine->data_prevista_consegna ? Carbon::parse($ordine->data_prevista_consegna)->format('d/m/Y') : ''],
        'L'  => ['Cod Carta',      $ordine->cod_carta ?? ''],
        'M'  => ['Carta',          $ordine->carta ?? ''],
        'N'  => ['Qta Carta',      (string)($ordine->qta_carta ?? '')],
        'O'  => ['UM Carta',       $ordine->UM_carta ?? ''],
        'P'  => ['Note Prestampa', $ordine->note_prestampa ?? ''],
        'Q'  => ['Responsabile',   $ordine->responsabile ?? ''],
        'R'  => ['Commento Prod.', $ordine->commento_produzione ?? ''],
        'S'  => ['Fase',           $nomeFase],
        'T'  => ['Reparto',        $repartoNome],
        'U'  => ['Operatori',      $operatoriNomi],
        'V'  => ['Qta Prod',       (string)($fase->qta_prod ?? '')],
        'W'  => ['Note',           $fase->note ?? ''],
        'X'  => ['Data Inizio',    $dataInizio],
        'Y'  => ['Data Fine',      $dataFine],
        'Z'  => ['Ordine Cliente', $ordine->ordine_cliente ?? ''],
        'AA' => ['DDT Vendita',    $ordine->numero_ddt_vendita ?? ''],
        'AB' => ['Vettore DDT',    $ordine->vettore_ddt ?? ''],
        'AC' => ['Qta DDT',        (string)($ordine->qta_ddt_vendita ?? '')],
        'AD' => ['Note Fasi Succ.', $ordine->note_fasi_successive ?? ''],
        'AE' => ['Colori',         DescrizioneParser::parseColori($ordine->descrizione ?? '', $ordine->cliente_nome ?? '', $repartoNome)],
        'AF' => ['Fustella',       DescrizioneParser::parseFustella($ordine->descrizione ?? '', $ordine->cliente_nome ?? '') ?? ''],
        'AG' => ['Esterno',        preg_match('/Inviato a:\s*(.+)/i', $fase->note ?? '', $mEst) ? trim($mEst[1]) : ''],
    ];

    $diffs = [];
    // Colonne numeriche
    $numCols = ['C', 'G', 'I', 'N', 'V', 'AC'];
    // Colonne data (solo data)
    $dateCols = ['J', 'K'];
    // Colonne datetime
    $dateTimeCols = ['X', 'Y'];

    foreach ($dbValues as $col => [$label, $dbVal]) {
        $exVal = $row[$col] ?? '';

        if (in_array($col, $numCols)) {
            $a = normNum($exVal);
            $b = normNum($dbVal);
        } elseif (in_array($col, $dateCols)) {
            $a = normDate($exVal);
            $b = norm($dbVal);
        } elseif (in_array($col, $dateTimeCols)) {
            $a = normDateTime($exVal);
            $b = norm($dbVal);
        } else {
            $a = norm($exVal);
            $b = norm($dbVal);
        }

        if ($a !== $b) {
            $exShort = mb_substr($a ?: '(vuoto)', 0, 50);
            $dbShort = mb_substr($b ?: '(vuoto)', 0, 50);
            $diffs[] = "{$col}:{$label}: Excel=\"{$exShort}\" DB=\"{$dbShort}\"";
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

// Controlla fasi nel DB non presenti nell'Excel
$idsExcel = [];
$isFirst2 = true;
foreach ($rows as $row2) {
    if ($isFirst2) { $isFirst2 = false; continue; }
    $id2 = $row2['A'] ?? null;
    if ($id2 && is_numeric($id2)) $idsExcel[] = (int)$id2;
}
$fasiDbMancanti = OrdineFase::where('stato', '<', 3)->whereNotIn('id', $idsExcel)->count();

echo "Righe Excel: {$righeExcel}\n";
echo "Righe trovate nel DB: {$righeDb}\n";
echo "Fasi nel DB (stato<3) non in Excel: {$fasiDbMancanti}\n\n";

if (empty($differenze)) {
    echo "Nessuna differenza - Excel e DB sono allineati.\n";
} else {
    echo sprintf("%-8s %-16s %-22s %s\n", 'ID', 'Commessa', 'Fase', 'Differenze');
    echo str_repeat('-', 120) . "\n";
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
