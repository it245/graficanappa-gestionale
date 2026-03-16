<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SchedulerExportService
{
    protected static array $nomiMacchine = [
        'XL106' => 'Heidelberg XL 106 (24h)',
        'BOBST' => 'BOBST 75x106',
        'STEL' => 'STEL G33/P25',
        'JOH' => 'JOH Stampa a Caldo',
        'PLAST' => 'Plastificatrice',
        'PIEGA' => 'Piegaincolla',
        'FIN' => 'Finestratrice',
        'INDIGO' => 'HP Indigo/MGI',
        'TAGLIO' => 'Tagliacarte',
        'LEGAT' => 'Legatoria',
        'ZUND' => 'Zünd',
    ];

    protected static array $coloriTab = [
        'XL106' => '2E75B6', 'BOBST' => 'C00000', 'STEL' => 'ED7D31',
        'JOH' => 'FFC000', 'PLAST' => '70AD47', 'PIEGA' => '7030A0',
        'FIN' => '00B0F0', 'INDIGO' => '808080', 'TAGLIO' => '404040',
        'LEGAT' => '996633', 'ZUND' => '006666',
    ];

    public static function export(string $path): void
    {
        $spreadsheet = new Spreadsheet();

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D0D0D0']]],
        ];
        $cellBorder = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D0D0D0']]],
        ];
        $ritardoFill = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF0F0']]];
        $ridottoFill = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']]];
        $altFill = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F7FB']]];

        // Foglio riepilogo
        $ws = $spreadsheet->getActiveSheet();
        $ws->setTitle('RIEPILOGO');
        $ws->setCellValue('A1', 'PIANO PRODUZIONE — GRAFICA NAPPA — Mossa 37');
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1F4E79');
        $ws->setCellValue('A2', 'Generato il ' . now()->format('d/m/Y H:i'));
        $ws->getStyle('A2')->getFont()->setItalic(true)->getColor()->setRGB('666666');

        $macchine = DB::table('ordine_fasi')
            ->whereNotNull('sched_macchina')
            ->selectRaw('sched_macchina, COUNT(*) as cnt, MIN(sched_inizio) as primo, MAX(sched_fine) as ultimo')
            ->groupBy('sched_macchina')
            ->orderBy('sched_macchina')
            ->get();

        $r = 4;
        $ws->setCellValue("A$r", 'Macchina');
        $ws->setCellValue("B$r", 'Fasi');
        $ws->setCellValue("C$r", 'Inizio');
        $ws->setCellValue("D$r", 'Fine');
        $ws->getStyle("A$r:D$r")->applyFromArray($headerStyle);
        $r++;

        foreach ($macchine as $m) {
            $ws->setCellValue("A$r", self::$nomiMacchine[$m->sched_macchina] ?? $m->sched_macchina);
            $ws->setCellValue("B$r", $m->cnt);
            $ws->setCellValue("C$r", $m->primo ? \Carbon\Carbon::parse($m->primo)->format('d/m H:i') : '-');
            $ws->setCellValue("D$r", $m->ultimo ? \Carbon\Carbon::parse($m->ultimo)->format('d/m H:i') : '-');
            $ws->getStyle("A$r:D$r")->applyFromArray($cellBorder);
            $r++;
        }
        $ws->getColumnDimension('A')->setWidth(30);
        $ws->getColumnDimension('B')->setWidth(8);
        $ws->getColumnDimension('C')->setWidth(14);
        $ws->getColumnDimension('D')->setWidth(14);

        // Foglio per macchina
        $headers = ['#', 'Commessa', 'Cliente', 'Descrizione', 'Fase', 'Fustella', 'Qta', 'Ore',
                     'Setup min', 'Tipo Setup', 'Inizio', 'Fine', 'Consegna', 'GG', 'Urgenza', 'Stato'];
        $colWidths = [5, 15, 22, 45, 20, 10, 8, 6, 6, 28, 14, 14, 12, 6, 12, 12];

        foreach ($macchine as $mac) {
            $mid = $mac->sched_macchina;
            $fasi = DB::table('ordine_fasi')
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->where('sched_macchina', $mid)
                ->select(
                    'ordine_fasi.sched_posizione', 'ordini.commessa', 'ordini.cliente_nome',
                    'ordini.descrizione', 'ordine_fasi.fase', 'ordine_fasi.sched_batch_group',
                    'ordine_fasi.qta_fase', 'ordine_fasi.sched_inizio', 'ordine_fasi.sched_fine',
                    'ordine_fasi.sched_setup_h', 'ordine_fasi.sched_setup_tipo',
                    'ordini.data_prevista_consegna', 'ordine_fasi.urgenza_reale',
                    'ordine_fasi.fascia_urgenza'
                )
                ->orderBy('sched_posizione')
                ->get();

            if ($fasi->isEmpty()) continue;

            $ws = $spreadsheet->createSheet();
            $title = substr($mid, 0, 31);
            $ws->setTitle($title);
            $ws->getTabColor()->setRGB(self::$coloriTab[$mid] ?? '333333');

            $nomeMac = self::$nomiMacchine[$mid] ?? $mid;
            $ws->setCellValue('A1', "$nomeMac — {$fasi->count()} fasi");
            $ws->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1F4E79');

            // Headers
            foreach ($headers as $c => $h) {
                $ws->setCellValueByColumnAndRow($c + 1, 3, $h);
            }
            $ws->getStyle('A3:P3')->applyFromArray($headerStyle);

            $labels = [0 => 'CRITICA', 1 => 'URGENTE', 2 => 'NORMALE', 3 => 'PIANIFICABILE'];

            foreach ($fasi as $i => $f) {
                $row = $i + 4;
                $consegna = $f->data_prevista_consegna ? \Carbon\Carbon::parse($f->data_prevista_consegna) : null;
                $fine = $f->sched_fine ? \Carbon\Carbon::parse($f->sched_fine) : null;
                $isRitardo = $consegna && $fine && $fine->gt($consegna);
                $isRidotto = $f->sched_setup_h < (25 / 60);

                $vals = [
                    $f->sched_posizione,
                    $f->commessa,
                    mb_substr($f->cliente_nome ?? '', 0, 22),
                    mb_substr($f->descrizione ?? '', 0, 45),
                    $f->fase,
                    $f->sched_batch_group ?? '',
                    $f->qta_fase ? number_format($f->qta_fase, 0, ',', '.') : '-',
                    $f->sched_setup_h ? round(($f->sched_fine ? \Carbon\Carbon::parse($f->sched_inizio)->diffInMinutes(\Carbon\Carbon::parse($f->sched_fine)) / 60 : 0), 2) : '-',
                    $f->sched_setup_h ? round($f->sched_setup_h * 60) : '-',
                    $f->sched_setup_tipo ?? '',
                    $f->sched_inizio ? \Carbon\Carbon::parse($f->sched_inizio)->format('d/m H:i') : '-',
                    $f->sched_fine ? \Carbon\Carbon::parse($f->sched_fine)->format('d/m H:i') : '-',
                    $consegna ? $consegna->format('d/m/Y') : '-',
                    $f->urgenza_reale !== null ? round($f->urgenza_reale, 1) : '-',
                    $labels[$f->fascia_urgenza] ?? '?',
                    $isRitardo ? 'RITARDO' : 'OK',
                ];

                foreach ($vals as $c => $v) {
                    $ws->setCellValueByColumnAndRow($c + 1, $row, $v);
                }

                $range = "A$row:P$row";
                $ws->getStyle($range)->applyFromArray($cellBorder);
                if ($isRitardo) {
                    $ws->getStyle($range)->applyFromArray($ritardoFill);
                } elseif ($isRidotto) {
                    $ws->getStyle($range)->applyFromArray($ridottoFill);
                } elseif ($i % 2 === 0) {
                    $ws->getStyle($range)->applyFromArray($altFill);
                }
            }

            foreach ($colWidths as $c => $w) {
                $ws->getColumnDimensionByColumn($c + 1)->setWidth($w);
            }
            $ws->freezePane('A4');
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
    }
}
