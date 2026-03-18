<?php
/**
 * Report Ore Lavorate per Macchina/Reparto
 * Genera un file Excel con ore lavorate, setup, produzione per macchina e operatore.
 *
 * Uso: php report_ore_macchine.php [data_inizio] [data_fine]
 * Es:  php report_ore_macchine.php 2026-03-10 2026-03-17
 *      php report_ore_macchine.php  (default: ultima settimana)
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Date range
$dataInizio = $argv[1] ?? Carbon::now()->startOfWeek()->format('Y-m-d');
$dataFine = $argv[2] ?? Carbon::now()->format('Y-m-d');

echo "Report Ore Macchine: $dataInizio → $dataFine\n\n";

$spreadsheet = new Spreadsheet();
$spreadsheet->getProperties()
    ->setCreator('MES Grafica Nappa')
    ->setTitle("Report Ore Macchine $dataInizio - $dataFine");

// === FOGLIO 1: RIEPILOGO PER REPARTO ===
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Riepilogo Reparti');

// Header
$headers = ['Reparto', 'Fasi Completate', 'Ore Avviamento', 'Ore Produzione', 'Ore Totali', 'Fogli Buoni', 'Fogli Scarto', '% Scarto'];
$col = 'A';
foreach ($headers as $h) {
    $sheet->setCellValue($col . '1', $h);
    $col++;
}

// Stile header
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D11317']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
];
$sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

// Dati per reparto
$reparti = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
    ->join('reparti', 'fasi_catalogo.reparto_id', '=', 'reparti.id')
    ->where('ordine_fasi.stato', '>=', 2)
    ->where(function ($q) use ($dataInizio, $dataFine) {
        $q->whereBetween('ordine_fasi.data_fine', [$dataInizio, $dataFine . ' 23:59:59'])
          ->orWhere(function ($q2) use ($dataInizio, $dataFine) {
              $q2->where('ordine_fasi.stato', 2)
                 ->where('ordine_fasi.data_inizio', '<=', $dataFine . ' 23:59:59');
          });
    })
    ->select(
        'reparti.nome as reparto',
        DB::raw('COUNT(DISTINCT ordine_fasi.id) as fasi_count'),
        DB::raw('SUM(COALESCE(ordine_fasi.tempo_avviamento_sec, 0)) as sec_avviamento'),
        DB::raw('SUM(COALESCE(ordine_fasi.tempo_esecuzione_sec, 0)) as sec_produzione'),
        DB::raw('SUM(COALESCE(ordine_fasi.fogli_buoni, 0)) as fogli_buoni'),
        DB::raw('SUM(COALESCE(ordine_fasi.fogli_scarto, 0)) as fogli_scarto')
    )
    ->groupBy('reparti.nome')
    ->orderBy('reparti.nome')
    ->get();

// Ore dalla pivot operatore per reparto (per reparti senza Prinect)
// Usa lo stesso filtro date della query principale (ordine_fasi.data_fine nel range O stato 2)
$orePivotPerReparto = DB::table('fase_operatore')
    ->join('ordine_fasi', 'fase_operatore.fase_id', '=', 'ordine_fasi.id')
    ->join('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
    ->join('reparti', 'fasi_catalogo.reparto_id', '=', 'reparti.id')
    ->whereNotNull('fase_operatore.data_inizio')
    ->whereNotNull('fase_operatore.data_fine')
    ->where('ordine_fasi.stato', '>=', 2)
    ->where(function ($q) use ($dataInizio, $dataFine) {
        $q->whereBetween('ordine_fasi.data_fine', [$dataInizio, $dataFine . ' 23:59:59'])
          ->orWhere(function ($q2) use ($dataInizio, $dataFine) {
              $q2->where('ordine_fasi.stato', 2)
                 ->where('ordine_fasi.data_inizio', '<=', $dataFine . ' 23:59:59');
          });
    })
    ->select(
        'reparti.nome as reparto',
        DB::raw('SUM(GREATEST(TIMESTAMPDIFF(SECOND, fase_operatore.data_inizio, fase_operatore.data_fine), 0)) as sec_lordo'),
        DB::raw('SUM(LEAST(COALESCE(fase_operatore.secondi_pausa, 0), TIMESTAMPDIFF(SECOND, fase_operatore.data_inizio, fase_operatore.data_fine))) as sec_pausa')
    )
    ->groupBy('reparti.nome')
    ->get()
    ->keyBy('reparto');

$row = 2;
$totFasi = 0; $totOre = 0; $totBuoni = 0; $totScarto = 0;
foreach ($reparti as $r) {
    // Ore Prinect (avviamento + produzione)
    $secPrinect = $r->sec_avviamento + $r->sec_produzione;

    // Se non ci sono ore Prinect, usa la pivot operatore
    if ($secPrinect <= 0) {
        $pivot = $orePivotPerReparto->get($r->reparto);
        $secNetto = $pivot ? max($pivot->sec_lordo - $pivot->sec_pausa, 0) : 0;
    } else {
        $secNetto = $secPrinect;
    }

    $oreAvv = round($r->sec_avviamento / 3600, 1);
    $oreProd = round($r->sec_produzione / 3600, 1);
    $oreTot = round($secNetto / 3600, 1);
    $pctScarto = ($r->fogli_buoni + $r->fogli_scarto) > 0
        ? round($r->fogli_scarto / ($r->fogli_buoni + $r->fogli_scarto) * 100, 1)
        : 0;

    $sheet->setCellValue("A$row", ucfirst($r->reparto));
    $sheet->setCellValue("B$row", $r->fasi_count);
    $sheet->setCellValue("C$row", $oreAvv);
    $sheet->setCellValue("D$row", $oreProd);
    $sheet->setCellValue("E$row", $oreTot);
    $sheet->setCellValue("F$row", $r->fogli_buoni);
    $sheet->setCellValue("G$row", $r->fogli_scarto);
    $sheet->setCellValue("H$row", $pctScarto . '%');

    $totFasi += $r->fasi_count;
    $totOre += $secNetto;
    $totBuoni += $r->fogli_buoni;
    $totScarto += $r->fogli_scarto;
    $row++;
}

// Riga totale
$sheet->setCellValue("A$row", 'TOTALE');
$sheet->setCellValue("B$row", $totFasi);
$sheet->setCellValue("C$row", '-');
$sheet->setCellValue("D$row", '-');
$sheet->setCellValue("E$row", round($totOre / 3600, 1));
$sheet->setCellValue("F$row", $totBuoni);
$sheet->setCellValue("G$row", $totScarto);
$pctTot = ($totBuoni + $totScarto) > 0 ? round($totScarto / ($totBuoni + $totScarto) * 100, 1) : 0;
$sheet->setCellValue("H$row", $pctTot . '%');
$sheet->getStyle("A$row:H$row")->getFont()->setBold(true);
$sheet->getStyle("A2:H$row")->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);

// Auto-width colonne
foreach (range('A', 'H') as $c) $sheet->getColumnDimension($c)->setAutoSize(true);

// === FOGLIO 2: PRINECT XL106 (dettaglio) ===
$sheetPrinect = $spreadsheet->createSheet();
$sheetPrinect->setTitle('Prinect XL106');

$headersPrinect = ['Commessa', 'Cliente', 'Job Name', 'Operatore', 'Fogli Buoni', 'Fogli Scarto', '% Scarto', 'T. Avviamento', 'T. Produzione', 'T. Totale', 'Attività'];
$col = 'A';
foreach ($headersPrinect as $h) {
    $sheetPrinect->setCellValue($col . '1', $h);
    $col++;
}
$sheetPrinect->getStyle('A1:K1')->applyFromArray($headerStyle);

$prinect = DB::table('prinect_attivita')
    ->whereBetween('start_time', [$dataInizio, $dataFine . ' 23:59:59'])
    ->whereNotNull('commessa_gestionale')
    ->select(
        'commessa_gestionale',
        'prinect_job_name',
        'operatore_prinect',
        DB::raw('SUM(good_cycles) as buoni'),
        DB::raw('SUM(waste_cycles) as scarti'),
        DB::raw('COUNT(*) as attivita'),
        DB::raw("SUM(CASE WHEN activity_name = 'Avviamento' THEN TIMESTAMPDIFF(SECOND, start_time, end_time) ELSE 0 END) as sec_avv"),
        DB::raw("SUM(CASE WHEN activity_name != 'Avviamento' THEN TIMESTAMPDIFF(SECOND, start_time, end_time) ELSE 0 END) as sec_prod")
    )
    ->groupBy('commessa_gestionale', 'prinect_job_name', 'operatore_prinect')
    ->orderBy('commessa_gestionale')
    ->get();

$row = 2;
foreach ($prinect as $p) {
    $ordine = DB::table('ordini')->where('commessa', $p->commessa_gestionale)->first();
    $oreTot = ($p->sec_avv + $p->sec_prod) / 3600;
    $pctSc = ($p->buoni + $p->scarti) > 0 ? round($p->scarti / ($p->buoni + $p->scarti) * 100, 1) : 0;

    $sheetPrinect->setCellValue("A$row", $p->commessa_gestionale);
    $sheetPrinect->setCellValue("B$row", $ordine->cliente_nome ?? '-');
    $sheetPrinect->setCellValue("C$row", $p->prinect_job_name ?? '-');
    $sheetPrinect->setCellValue("D$row", $p->operatore_prinect ?? '-');
    $sheetPrinect->setCellValue("E$row", $p->buoni);
    $sheetPrinect->setCellValue("F$row", $p->scarti);
    $sheetPrinect->setCellValue("G$row", $pctSc . '%');

    $formatTempo = function ($sec) {
        if ($sec <= 0) return '-';
        $h = floor($sec / 3600);
        $m = floor(($sec % 3600) / 60);
        return ($h > 0 ? "{$h}h " : '') . "{$m}m";
    };

    $sheetPrinect->setCellValue("H$row", $formatTempo($p->sec_avv));
    $sheetPrinect->setCellValue("I$row", $formatTempo($p->sec_prod));
    $sheetPrinect->setCellValue("J$row", $formatTempo($p->sec_avv + $p->sec_prod));
    $sheetPrinect->setCellValue("K$row", $p->attivita);
    $row++;
}
$sheetPrinect->getStyle("A2:K$row")->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
foreach (range('A', 'K') as $c) $sheetPrinect->getColumnDimension($c)->setAutoSize(true);

// === FOGLIO 3: ORE PER OPERATORE ===
$sheetOp = $spreadsheet->createSheet();
$sheetOp->setTitle('Ore Operatori');

$headersOp = ['Operatore', 'Reparto', 'Fasi Lavorate', 'Ore Lorde', 'Ore Pausa', 'Ore Nette', 'Ore Prinect'];
$col = 'A';
foreach ($headersOp as $h) {
    $sheetOp->setCellValue($col . '1', $h);
    $col++;
}
$sheetOp->getStyle('A1:G1')->applyFromArray($headerStyle);

$operatori = DB::table('fase_operatore')
    ->join('operatori', 'fase_operatore.operatore_id', '=', 'operatori.id')
    ->leftJoin('ordine_fasi', 'fase_operatore.fase_id', '=', 'ordine_fasi.id')
    ->where(function ($q) use ($dataInizio, $dataFine) {
        $q->whereBetween('fase_operatore.data_fine', [$dataInizio, $dataFine . ' 23:59:59'])
          ->orWhere(function ($q2) use ($dataInizio, $dataFine) {
              $q2->whereNull('fase_operatore.data_fine')
                 ->whereBetween('fase_operatore.data_inizio', [$dataInizio, $dataFine . ' 23:59:59']);
          });
    })
    ->select(
        'operatori.nome',
        'operatori.cognome',
        DB::raw('COUNT(DISTINCT fase_operatore.fase_id) as fasi_count'),
        DB::raw('SUM(CASE WHEN fase_operatore.data_fine IS NOT NULL THEN TIMESTAMPDIFF(SECOND, fase_operatore.data_inizio, fase_operatore.data_fine) ELSE 0 END) as sec_lordo'),
        DB::raw('SUM(COALESCE(fase_operatore.secondi_pausa, 0)) as sec_pausa'),
        DB::raw('SUM(COALESCE(ordine_fasi.tempo_avviamento_sec, 0) + COALESCE(ordine_fasi.tempo_esecuzione_sec, 0)) as sec_prinect')
    )
    ->groupBy('operatori.nome', 'operatori.cognome')
    ->orderBy('operatori.cognome')
    ->get();

$row = 2;
foreach ($operatori as $op) {
    $oreNette = max($op->sec_lordo - $op->sec_pausa, 0) / 3600;
    $orePrinect = $op->sec_prinect / 3600;

    $sheetOp->setCellValue("A$row", $op->nome . ' ' . $op->cognome);
    $sheetOp->setCellValue("B$row", '-'); // Reparto lo prenderemmo dalla relazione
    $sheetOp->setCellValue("C$row", $op->fasi_count);
    $sheetOp->setCellValue("D$row", round($op->sec_lordo / 3600, 1));
    $sheetOp->setCellValue("E$row", round($op->sec_pausa / 3600, 1));
    $sheetOp->setCellValue("F$row", round($oreNette, 1));
    $sheetOp->setCellValue("G$row", round($orePrinect, 1));
    $row++;
}
$sheetOp->getStyle("A2:G$row")->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
foreach (range('A', 'G') as $c) $sheetOp->getColumnDimension($c)->setAutoSize(true);

// === FOGLIO 4: DETTAGLIO PER COMMESSA ===
$sheetComm = $spreadsheet->createSheet();
$sheetComm->setTitle('Dettaglio Commesse');

$headersComm = ['Commessa', 'Cliente', 'Descrizione', 'Fase', 'Reparto', 'Stato', 'Operatori', 'Ore Avv.', 'Ore Prod.', 'Ore Tot.', 'Fogli Buoni', 'Fogli Scarto'];
$col = 'A';
foreach ($headersComm as $h) {
    $sheetComm->setCellValue($col . '1', $h);
    $col++;
}
$sheetComm->getStyle('A1:L1')->applyFromArray($headerStyle);

$fasiDettaglio = DB::table('ordine_fasi')
    ->join('ordini', 'ordine_fasi.ordine_id', '=', 'ordini.id')
    ->leftJoin('fasi_catalogo', 'ordine_fasi.fase_catalogo_id', '=', 'fasi_catalogo.id')
    ->leftJoin('reparti', 'fasi_catalogo.reparto_id', '=', 'reparti.id')
    ->where('ordine_fasi.stato', '>=', 2)
    ->where(function ($q) use ($dataInizio, $dataFine) {
        $q->whereBetween('ordine_fasi.data_fine', [$dataInizio, $dataFine . ' 23:59:59'])
          ->orWhere(function ($q2) use ($dataInizio, $dataFine) {
              $q2->where('ordine_fasi.stato', 2)
                 ->where('ordine_fasi.data_inizio', '<=', $dataFine . ' 23:59:59');
          });
    })
    ->select(
        'ordini.commessa', 'ordini.cliente_nome', 'ordini.descrizione',
        'ordine_fasi.fase', 'reparti.nome as reparto', 'ordine_fasi.stato',
        'ordine_fasi.tempo_avviamento_sec', 'ordine_fasi.tempo_esecuzione_sec',
        'ordine_fasi.fogli_buoni', 'ordine_fasi.fogli_scarto', 'ordine_fasi.id as fase_id'
    )
    ->orderBy('ordini.commessa')
    ->orderBy('ordine_fasi.priorita')
    ->get();

$statiLabel = [0 => 'Caricato', 1 => 'Pronto', 2 => 'Avviato', 3 => 'Terminato', 4 => 'Consegnato'];
$row = 2;
foreach ($fasiDettaglio as $f) {
    $secAvv = $f->tempo_avviamento_sec ?? 0;
    $secProd = $f->tempo_esecuzione_sec ?? 0;
    $formatTempo = function ($sec) {
        if ($sec <= 0) return '-';
        $h = floor($sec / 3600);
        $m = floor(($sec % 3600) / 60);
        return ($h > 0 ? "{$h}h " : '') . "{$m}m";
    };

    // Operatori dalla pivot
    $ops = DB::table('fase_operatore')
        ->join('operatori', 'fase_operatore.operatore_id', '=', 'operatori.id')
        ->where('fase_operatore.fase_id', $f->fase_id)
        ->select(DB::raw("CONCAT(operatori.nome, ' ', operatori.cognome) as nome_completo"))
        ->pluck('nome_completo')
        ->implode(', ');

    $sheetComm->setCellValue("A$row", $f->commessa);
    $sheetComm->setCellValue("B$row", $f->cliente_nome ?? '-');
    $sheetComm->setCellValue("C$row", substr($f->descrizione ?? '-', 0, 50));
    $sheetComm->setCellValue("D$row", $f->fase);
    $sheetComm->setCellValue("E$row", ucfirst($f->reparto ?? '-'));
    $sheetComm->setCellValue("F$row", $statiLabel[$f->stato] ?? $f->stato);
    $sheetComm->setCellValue("G$row", $ops ?: '-');
    $sheetComm->setCellValue("H$row", $formatTempo($secAvv));
    $sheetComm->setCellValue("I$row", $formatTempo($secProd));
    $sheetComm->setCellValue("J$row", $formatTempo($secAvv + $secProd));
    $sheetComm->setCellValue("K$row", $f->fogli_buoni ?? 0);
    $sheetComm->setCellValue("L$row", $f->fogli_scarto ?? 0);
    $row++;
}
$sheetComm->getStyle("A2:L$row")->applyFromArray(['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]]);
foreach (range('A', 'L') as $c) $sheetComm->getColumnDimension($c)->setAutoSize(true);

// === SALVA FILE ===
$nomeFile = "Report_Ore_Macchine_{$dataInizio}_{$dataFine}.xlsx";
$percorso = __DIR__ . '/storage/' . $nomeFile;

$writer = new Xlsx($spreadsheet);
$writer->save($percorso);

echo "Report generato: $percorso\n";
echo "4 fogli: Riepilogo Reparti, Prinect XL106, Ore Operatori, Dettaglio Commesse\n";
