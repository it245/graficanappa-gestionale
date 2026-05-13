<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$rows = DB::table('ordine_fasi as f')
    ->join('ordini as o', 'o.id', '=', 'f.ordine_id')
    ->join('fasi_catalogo as fc', 'fc.id', '=', 'f.fase_catalogo_id')
    ->join('reparti as r', 'r.id', '=', 'fc.reparto_id')
    ->whereRaw('LOWER(r.nome) = ?', ['stampa offset'])
    ->where('f.stato', '3')
    ->whereNull('f.deleted_at')
    ->where(function ($q) { $q->whereNull('f.scarti')->orWhere('f.scarti', 0); })
    ->where('f.qta_prod', '>', 0)
    ->select('o.commessa','o.cliente_nome','o.descrizione','f.fase','f.qta_prod','f.scarti_previsti','f.data_fine')
    ->orderBy('f.data_fine', 'desc')
    ->get();

$sp = new Spreadsheet();
$s = $sp->getActiveSheet();
$s->setTitle('Scarti mancanti');
$s->setCellValue('A1', 'Commessa');
$s->setCellValue('B1', 'Cliente');
$s->setCellValue('C1', 'Descrizione');
$s->setCellValue('D1', 'Fase');
$s->setCellValue('E1', 'Qta Prodotta');
$s->setCellValue('F1', 'Scarti Previsti');
$s->setCellValue('G1', 'Data Fine');

$row = 2;
foreach ($rows as $r) {
    $s->setCellValue("A$row", $r->commessa);
    $s->setCellValue("B$row", $r->cliente_nome);
    $s->setCellValue("C$row", $r->descrizione);
    $s->setCellValue("D$row", $r->fase);
    $s->setCellValue("E$row", $r->qta_prod);
    $s->setCellValue("F$row", $r->scarti_previsti ?? '');
    $s->setCellValue("G$row", $r->data_fine);
    $row++;
}

// Bold header + auto-size
foreach (['A','B','C','D','E','F','G'] as $col) {
    $s->getStyle("{$col}1")->getFont()->setBold(true);
    $s->getColumnDimension($col)->setAutoSize(true);
}

$path = __DIR__ . '/scarti_mancanti_' . date('Ymd_His') . '.xlsx';
(new Xlsx($sp))->save($path);
echo "Excel salvato: $path\n";
echo "Totale righe: " . count($rows) . "\n";
