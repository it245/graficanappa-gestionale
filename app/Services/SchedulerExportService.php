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
        'SPED' => 'Spedizione',
    ];

    protected static array $coloriTab = [
        'XL106' => '2E75B6', 'BOBST' => 'C00000', 'STEL' => 'ED7D31',
        'JOH' => 'FFC000', 'PLAST' => '70AD47', 'PIEGA' => '7030A0',
        'FIN' => '00B0F0', 'INDIGO' => '808080', 'TAGLIO' => '404040',
        'LEGAT' => '996633', 'ZUND' => '006666', 'SPED' => '333333',
    ];

    public static function export(string $path): void
    {
        error_reporting(E_ALL & ~E_DEPRECATED);

        $spreadsheet = new Spreadsheet();

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Arial', 'size' => 10],
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
        $titleFill = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D6E4F0']]];

        // === FOGLIO RIEPILOGO ===
        $ws = $spreadsheet->getActiveSheet();
        $ws->setTitle('RIEPILOGO');

        $ws->mergeCells('A1:I1');
        $ws->setCellValue('A1', 'PIANO PRODUZIONE — GRAFICA NAPPA — Mossa 37');
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1F4E79');
        $ws->getStyle('A1')->applyFromArray($titleFill);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $ws->mergeCells('A2:I2');
        $ws->setCellValue('A2', 'Simulazione da ' . now()->format('d/m/Y H:i') . ' | Propagazione fasi | XL106=24h');
        $ws->getStyle('A2')->getFont()->setItalic(true)->setSize(9)->getColor()->setRGB('666666');
        $ws->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // KPI
        $macchine = DB::table('ordine_fasi')
            ->whereNotNull('sched_macchina')
            ->selectRaw('sched_macchina, COUNT(*) as cnt, MIN(sched_inizio) as primo, MAX(sched_fine) as ultimo')
            ->groupBy('sched_macchina')
            ->orderBy('sched_macchina')
            ->get();

        $totFasi = DB::table('ordine_fasi')->whereIn('stato', [0, 1, 2])->whereNull('deleted_at')->count();
        $totSched = $macchine->sum('cnt');
        $setupPieno = 25 / 60;
        $tsSenza = $totSched * $setupPieno;
        $tsCon = DB::table('ordine_fasi')->whereNotNull('sched_macchina')->sum('sched_setup_h');

        $r = 4;
        foreach ([
            ['Fasi totali (stato 0+1+2)', $totFasi],
            ['Schedulate con propagazione', $totSched],
            ['Setup senza batching', round($tsSenza, 1) . 'h'],
            ['Setup con batching', round($tsCon, 1) . 'h'],
            ['RISPARMIO', round($tsSenza - $tsCon, 1) . 'h (' . round(($tsSenza - $tsCon) * 60) . ' min)'],
        ] as [$label, $val]) {
            $ws->setCellValue("A$r", $label);
            $ws->getStyle("A$r")->getFont()->setBold(true)->setName('Arial')->setSize(9);
            $ws->setCellValue("C$r", $val);
            $ws->getStyle("C$r")->getFont()->setName('Arial')->setSize(9);
            $r++;
        }

        // Tabella macchine
        $r += 1;
        $ws->setCellValue("A$r", 'PER MACCHINA');
        $ws->getStyle("A$r")->getFont()->setBold(true)->setSize(11)->getColor()->setRGB('1F4E79');
        $r++;

        $hdrs = ['Macchina', 'Fasi', 'Inizio', 'Fine', 'Ritardo', '%', 'Note'];
        foreach ($hdrs as $c => $h) {
            $ws->setCellValueByColumnAndRow($c + 1, $r, $h);
        }
        $ws->getStyle("A$r:G$r")->applyFromArray($headerStyle);
        $r++;

        foreach ($macchine as $m) {
            $mid = $m->sched_macchina;
            $ritardi = DB::table('ordine_fasi')
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->where('sched_macchina', $mid)
                ->whereNotNull('sched_fine')
                ->whereNotNull('ordini.data_prevista_consegna')
                ->whereRaw('sched_fine > ordini.data_prevista_consegna')
                ->count();
            $propagate = DB::table('ordine_fasi')->where('sched_macchina', $mid)->where('stato', 0)->count();
            $pctRit = $m->cnt > 0 ? round($ritardi / $m->cnt * 100) : 0;

            $vals = [
                self::$nomiMacchine[$mid] ?? $mid,
                $m->cnt,
                $m->primo ? \Carbon\Carbon::parse($m->primo)->format('d/m H:i') : '-',
                $m->ultimo ? \Carbon\Carbon::parse($m->ultimo)->format('d/m H:i') : '-',
                $ritardi,
                "$pctRit%",
                $propagate > 0 ? "$propagate propagate" : '',
            ];
            foreach ($vals as $c => $v) {
                $ws->setCellValueByColumnAndRow($c + 1, $r, $v);
            }
            $ws->getStyle("A$r:G$r")->applyFromArray($cellBorder);
            if ($r % 2 === 0) $ws->getStyle("A$r:G$r")->applyFromArray($altFill);
            $r++;
        }

        foreach ([1 => 30, 2 => 6, 3 => 14, 4 => 14, 5 => 8, 6 => 6, 7 => 16] as $c => $w) {
            $ws->getColumnDimensionByColumn($c)->setWidth($w);
        }

        // === FOGLI PER MACCHINA ===
        $headers = ['#', 'Commessa', 'Cliente', 'Descrizione', 'Fase', 'FS', 'Copie', 'Fogli',
                     'Colori', 'Ore', 'Setup', 'Tipo Setup', 'Inizio', 'Fine', 'Consegna', 'GG',
                     'Propagata', 'Stato'];
        $colWidths = [5, 15, 22, 45, 20, 10, 10, 10, 22, 6, 6, 28, 14, 14, 12, 6, 12, 12];
        $NC = count($headers);

        foreach ($macchine as $mac) {
            $mid = $mac->sched_macchina;
            $fasi = DB::table('ordine_fasi')
                ->join('ordini', 'ordini.id', '=', 'ordine_fasi.ordine_id')
                ->where('sched_macchina', $mid)
                ->select(
                    'ordine_fasi.sched_posizione', 'ordini.commessa', 'ordini.cliente_nome',
                    'ordini.descrizione', 'ordine_fasi.fase', 'ordine_fasi.sched_batch_group',
                    'ordine_fasi.qta_fase', 'ordini.qta_richiesta', 'ordini.qta_carta',
                    'ordine_fasi.sched_inizio', 'ordine_fasi.sched_fine',
                    'ordine_fasi.sched_setup_h', 'ordine_fasi.sched_setup_tipo',
                    'ordini.data_prevista_consegna', 'ordine_fasi.urgenza_reale',
                    'ordine_fasi.fascia_urgenza', 'ordine_fasi.stato'
                )
                ->orderBy('sched_posizione')
                ->get();

            if ($fasi->isEmpty()) continue;

            $ws = $spreadsheet->createSheet();
            $ws->setTitle(substr($mid, 0, 31));
            $ws->getTabColor()->setRGB(self::$coloriTab[$mid] ?? '333333');

            $nomeMac = self::$nomiMacchine[$mid] ?? $mid;
            $turniLbl = $mid === 'XL106' ? '24h lun-ven' : '6-22 lun-ven';
            $ws->mergeCells("A1:" . chr(64 + $NC) . "1");
            $ws->setCellValue('A1', "$nomeMac ($turniLbl) — {$fasi->count()} fasi");
            $ws->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('1F4E79');
            $ws->getStyle('A1')->applyFromArray($titleFill);
            $ws->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            foreach ($headers as $c => $h) {
                $ws->setCellValueByColumnAndRow($c + 1, 3, $h);
            }
            $lastCol = chr(64 + $NC);
            $ws->getStyle("A3:{$lastCol}3")->applyFromArray($headerStyle);

            foreach ($fasi as $i => $f) {
                $row = $i + 4;
                $consegna = $f->data_prevista_consegna ? \Carbon\Carbon::parse($f->data_prevista_consegna) : null;
                $fine = $f->sched_fine ? \Carbon\Carbon::parse($f->sched_fine) : null;
                $isRitardo = $consegna && $fine && $fine->gt($consegna);
                $isRidotto = $f->sched_setup_h && $f->sched_setup_h < (25 / 60);

                // Calcola ore lavorazione
                $inizio = $f->sched_inizio ? \Carbon\Carbon::parse($f->sched_inizio) : null;
                $oreLav = ($inizio && $fine) ? round($inizio->diffInMinutes($fine) / 60, 2) : '-';

                // Parse colori dalla descrizione
                $colori = '';
                if ($f->descrizione) {
                    $colori = \App\Helpers\DescrizioneParser::parseColori($f->descrizione, $f->cliente_nome ?? '');
                }

                // Propagata
                $propagata = ($f->stato == 1 || ($f->stato == 0 && in_array($mid, ['XL106', 'INDIGO'])))
                    ? 'PRONTA' : 'PROPAGATA';

                $vals = [
                    $f->sched_posizione,
                    $f->commessa,
                    mb_substr($f->cliente_nome ?? '', 0, 22),
                    mb_substr($f->descrizione ?? '', 0, 45),
                    $f->fase,
                    $f->sched_batch_group ?? '',
                    $f->qta_richiesta ? number_format($f->qta_richiesta, 0, ',', '.') : '',
                    $f->qta_fase ? number_format($f->qta_fase, 0, ',', '.') : '',
                    mb_substr($colori, 0, 30),
                    $oreLav,
                    $f->sched_setup_h ? round($f->sched_setup_h * 60) : '-',
                    $f->sched_setup_tipo ?? '',
                    $f->sched_inizio ? \Carbon\Carbon::parse($f->sched_inizio)->format('d/m H:i') : '-',
                    $f->sched_fine ? \Carbon\Carbon::parse($f->sched_fine)->format('d/m H:i') : '-',
                    $consegna ? $consegna->format('d/m/Y') : '-',
                    $f->urgenza_reale !== null ? round($f->urgenza_reale, 1) : '-',
                    $propagata,
                    $isRitardo ? 'RITARDO' : 'OK',
                ];

                foreach ($vals as $c => $v) {
                    $ws->setCellValueByColumnAndRow($c + 1, $row, $v);
                }

                $range = "A$row:{$lastCol}$row";
                $ws->getStyle($range)->applyFromArray($cellBorder);
                $ws->getStyle($range)->getFont()->setName('Arial')->setSize(9);

                if ($isRitardo) {
                    $ws->getStyle($range)->applyFromArray($ritardoFill);
                    $ws->getCellByColumnAndRow($NC, $row)->getStyle()->getFont()->setBold(true)->getColor()->setRGB('CC0000');
                } elseif ($isRidotto) {
                    $ws->getStyle($range)->applyFromArray($ridottoFill);
                    $ws->getCellByColumnAndRow($NC, $row)->getStyle()->getFont()->getColor()->setRGB('006600');
                } elseif ($i % 2 === 0) {
                    $ws->getStyle($range)->applyFromArray($altFill);
                    $ws->getCellByColumnAndRow($NC, $row)->getStyle()->getFont()->getColor()->setRGB('006600');
                } else {
                    $ws->getCellByColumnAndRow($NC, $row)->getStyle()->getFont()->getColor()->setRGB('006600');
                }
            }

            foreach ($colWidths as $c => $w) {
                $ws->getColumnDimensionByColumn($c + 1)->setWidth($w);
            }
            $ws->freezePane('A4');
            $ws->setAutoFilter("A3:{$lastCol}" . (3 + $fasi->count()));
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
    }
}
