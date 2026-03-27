<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class ExportPresenzeExcel extends Command
{
    protected $signature = 'presenze:export-excel {--giorni=30 : Quanti giorni di storico}';
    protected $description = 'Esporta storico presenze su Excel condiviso (dashboard_mes_presenze.xlsx)';

    public function handle()
    {
        $giorni = (int) $this->option('giorni');
        $dataInizio = Carbon::today()->subDays($giorni - 1);
        $oggi = Carbon::today();

        $this->info("Export presenze: ultimi {$giorni} giorni ({$dataInizio->format('d/m/Y')} → {$oggi->format('d/m/Y')})");

        $anagrafica = DB::table('nettime_anagrafica')->orderBy('cognome')->get();
        if ($anagrafica->isEmpty()) {
            $this->error('Nessuna anagrafica trovata');
            return 1;
        }

        $spreadsheet = new Spreadsheet();

        // ═══════════════════════════════════════
        // FOGLIO 1: PRESENZE OGGI (real-time)
        // ═══════════════════════════════════════
        $sheetOggi = $spreadsheet->getActiveSheet();
        $sheetOggi->setTitle('Oggi ' . $oggi->format('d-m'));
        $this->buildFoglioGiorno($sheetOggi, $anagrafica, $oggi);

        // ═══════════════════════════════════════
        // FOGLIO 2: RIEPILOGO MENSILE
        // ═══════════════════════════════════════
        $sheetRiepilogo = $spreadsheet->createSheet();
        $sheetRiepilogo->setTitle('Riepilogo');
        $this->buildFoglioRiepilogo($sheetRiepilogo, $anagrafica, $dataInizio, $oggi);

        // ═══════════════════════════════════════
        // FOGLI GIORNALIERI (ultimi 7 giorni)
        // ═══════════════════════════════════════
        for ($i = 1; $i <= min(7, $giorni); $i++) {
            $data = Carbon::today()->subDays($i);
            if ($data->isWeekend()) continue;
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($data->format('d-m D'));
            $this->buildFoglioGiorno($sheet, $anagrafica, $data);
        }

        // Salva
        $outDir = env('EXCEL_SYNC_PATH', storage_path('app/excel_sync'));
        $outFile = rtrim($outDir, '/\\') . DIRECTORY_SEPARATOR . 'dashboard_mes_presenze.xlsx';

        $writer = new Xlsx($spreadsheet);
        $writer->save($outFile);

        $this->info("Salvato: {$outFile}");
        $this->info("Aggiornato: " . now()->format('H:i:s'));

        return 0;
    }

    private function buildFoglioGiorno($sheet, $anagrafica, Carbon $data)
    {
        $dataStr = $data->format('Y-m-d');
        $isOggi = $data->isToday();

        // Timbrature del giorno
        $timbrature = DB::table('nettime_timbrature')
            ->whereDate('data_ora', $dataStr)
            ->orderBy('data_ora')
            ->get();

        // Turno notturno
        $ieri = $data->copy()->subDay()->format('Y-m-d');
        $entrateNotturne = DB::table('nettime_timbrature')
            ->where('verso', 'E')
            ->where('data_ora', '>=', "{$ieri} 20:00:00")
            ->where('data_ora', '<', "{$dataStr} 00:00:00")
            ->get();

        foreach ($entrateNotturne as $en) {
            $uscitaPrimaMezzanotte = DB::table('nettime_timbrature')
                ->where('matricola', $en->matricola)
                ->where('verso', 'U')
                ->where('data_ora', '>', $en->data_ora)
                ->where('data_ora', '<', "{$dataStr} 00:00:00")
                ->exists();
            if (!$uscitaPrimaMezzanotte) {
                $timbrature->push($en);
            }
        }

        $timbrature = $timbrature->sortBy('data_ora')->values();

        // Raggruppa per dipendente
        $perDipendente = [];
        foreach ($timbrature as $t) {
            $matr = $t->matricola;
            if (!isset($perDipendente[$matr])) {
                $anag = $anagrafica->firstWhere('matricola', $matr);
                $perDipendente[$matr] = [
                    'nome' => $anag ? "{$anag->cognome} {$anag->nome}" : "Matricola {$matr}",
                    'matricola' => $matr,
                    'timbrature' => [],
                ];
            }
            $perDipendente[$matr]['timbrature'][] = $t;
        }

        // Titolo
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'PRESENZE — GRAFICA NAPPA SRL');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'D11317']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(35);

        $sheet->mergeCells('A2:G2');
        $label = $data->format('d/m/Y (l)');
        $sheet->setCellValue('A2', $label . ($isOggi ? ' — Aggiornato ore ' . now()->format('H:i') : ''));
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '666666']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Header
        $headers = ['Dipendente', 'Matricola', 'Stato', 'Entrata', 'Uscita', 'Ore Lavorate', 'Pausa'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '3', $h);
            $col++;
        }
        $sheet->getStyle('A3:G3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D11317']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(30);

        // Dati
        $row = 4;
        $totPresenti = 0;
        $totUsciti = 0;

        foreach (collect($perDipendente)->sortBy('nome') as $dip) {
            $timb = $dip['timbrature'];

            // Pulizia E duplicate < 2h
            $timbPulite = [];
            for ($i = 0; $i < count($timb); $i++) {
                $curr = $timb[$i];
                $next = $timb[$i + 1] ?? null;
                if ($curr->verso === 'E' && $next && $next->verso === 'E') {
                    $diffMin = Carbon::parse($curr->data_ora)->diffInMinutes(Carbon::parse($next->data_ora));
                    if ($diffMin < 120) continue;
                }
                $timbPulite[] = $curr;
            }
            $timb = $timbPulite;

            $entrate = array_filter($timb, fn($t) => $t->verso === 'E');
            $uscite = array_filter($timb, fn($t) => $t->verso === 'U');

            $primaEntrata = !empty($entrate) ? Carbon::parse(min(array_map(fn($t) => $t->data_ora, $entrate))) : null;
            $ultimaUscita = !empty($uscite) ? Carbon::parse(max(array_map(fn($t) => $t->data_ora, $uscite))) : null;

            $ultimaTimb = end($timb);
            $presente = $ultimaTimb && $ultimaTimb->verso === 'E';
            if ($presente) $totPresenti++; else $totUsciti++;

            // Calcola ore lavorate e pausa
            $oreLavoro = 0;
            $orePausa = 0;
            $entr = null;
            $finePrec = null;
            foreach ($timb as $t) {
                if ($t->verso === 'E') {
                    if ($finePrec) {
                        $orePausa += Carbon::parse($finePrec)->diffInMinutes(Carbon::parse($t->data_ora));
                    }
                    $entr = Carbon::parse($t->data_ora);
                } elseif ($t->verso === 'U' && $entr) {
                    $oreLavoro += $entr->diffInMinutes(Carbon::parse($t->data_ora));
                    $finePrec = $t->data_ora;
                    $entr = null;
                }
            }
            if ($entr && $isOggi) {
                $oreLavoro += $entr->diffInMinutes(now());
            }

            $sheet->setCellValue('A' . $row, $dip['nome']);
            $sheet->setCellValueExplicit('B' . $row, $dip['matricola'], DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $row, $presente ? 'PRESENTE' : 'USCITO');
            $sheet->setCellValue('D' . $row, $primaEntrata ? $primaEntrata->format('H:i') : '-');
            $sheet->setCellValue('E' . $row, $ultimaUscita ? $ultimaUscita->format('H:i') : '-');
            $sheet->setCellValue('F' . $row, sprintf('%dh %02dm', intdiv($oreLavoro, 60), $oreLavoro % 60));
            $sheet->setCellValue('G' . $row, $orePausa > 0 ? sprintf('%dm', $orePausa) : '-');

            // Colore stato
            $statoColor = $presente ? '22C55E' : 'EF4444';
            $sheet->getStyle("C{$row}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $statoColor]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:B{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F5F5F5');
                $sheet->getStyle("D{$row}:G{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F5F5F5');
            }

            $row++;
        }

        // Totale
        $lastRow = $row - 1;
        if ($lastRow >= 4) {
            $sheet->getStyle("A4:G{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'font' => ['size' => 11],
            ]);
        }

        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->setCellValue("A{$row}", "Presenti: {$totPresenti} | Usciti: {$totUsciti} | Totale: " . ($totPresenti + $totUsciti));
        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8E8E8']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Larghezze
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(10);
    }

    private function buildFoglioRiepilogo($sheet, $anagrafica, Carbon $dataInizio, Carbon $oggi)
    {
        // Genera lista giorni lavorativi (lun-ven)
        $giorni = [];
        $cur = $dataInizio->copy();
        while ($cur->lte($oggi)) {
            if (!$cur->isWeekend()) {
                $giorni[] = $cur->copy();
            }
            $cur->addDay();
        }

        $lastCol = chr(ord('B') + count($giorni)); // colonna dopo l'ultimo giorno

        // Titolo
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', 'RIEPILOGO PRESENZE — ' . $dataInizio->format('d/m/Y') . ' → ' . $oggi->format('d/m/Y'));
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'D11317']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(35);

        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', 'Aggiornato: ' . now()->format('d/m/Y H:i'));
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '666666']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Header: Dipendente + un colonna per ogni giorno
        $sheet->setCellValue('A3', 'Dipendente');
        $col = 'B';
        foreach ($giorni as $g) {
            $sheet->setCellValue($col . '3', $g->format('d/m'));
            $sheet->getColumnDimension($col)->setWidth(8);
            $col++;
        }
        // Colonna totale ore
        $sheet->setCellValue($col . '3', 'Tot Ore');
        $colTot = $col;
        $sheet->getColumnDimension($colTot)->setWidth(10);

        $headerRange = "A3:{$colTot}3";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(28);
        $sheet->getColumnDimension('A')->setWidth(25);

        // Precalcola prima entrata per ogni dipendente per ogni giorno
        $timbratureTutte = DB::table('nettime_timbrature')
            ->where('verso', 'E')
            ->where('data_ora', '>=', $dataInizio->format('Y-m-d'))
            ->select('matricola', DB::raw('DATE(data_ora) as giorno'), DB::raw('MIN(data_ora) as prima_entrata'))
            ->groupBy('matricola', DB::raw('DATE(data_ora)'))
            ->get();

        // Mappa: matricola → giorno → ora entrata
        $mappa = [];
        foreach ($timbratureTutte as $t) {
            $mappa[$t->matricola][$t->giorno] = Carbon::parse($t->prima_entrata)->format('H:i');
        }

        // Ore lavorate per giorno: somma intervalli E→U
        $orePerGiorno = [];
        $timbratureFull = DB::table('nettime_timbrature')
            ->where('data_ora', '>=', $dataInizio->format('Y-m-d'))
            ->orderBy('matricola')
            ->orderBy('data_ora')
            ->get()
            ->groupBy('matricola');

        foreach ($timbratureFull as $matricola => $timbList) {
            $perGiorno = $timbList->groupBy(fn($t) => Carbon::parse($t->data_ora)->format('Y-m-d'));
            foreach ($perGiorno as $giorno => $timb) {
                $minuti = 0;
                $entr = null;
                foreach ($timb->sortBy('data_ora') as $t) {
                    if ($t->verso === 'E') {
                        $entr = Carbon::parse($t->data_ora);
                    } elseif ($t->verso === 'U' && $entr) {
                        $minuti += $entr->diffInMinutes(Carbon::parse($t->data_ora));
                        $entr = null;
                    }
                }
                $orePerGiorno[$matricola][$giorno] = $minuti;
            }
        }

        // Dati
        $row = 4;
        foreach ($anagrafica as $anag) {
            if (!isset($mappa[$anag->matricola])) continue;

            $sheet->setCellValue('A' . $row, "{$anag->cognome} {$anag->nome}");

            $col = 'B';
            $totMinuti = 0;
            foreach ($giorni as $g) {
                $giornoStr = $g->format('Y-m-d');
                $entrata = $mappa[$anag->matricola][$giornoStr] ?? null;
                $minGiorno = $orePerGiorno[$anag->matricola][$giornoStr] ?? 0;
                $totMinuti += $minGiorno;

                if ($entrata) {
                    $oreStr = $minGiorno > 0 ? sprintf('%dh%02d', intdiv($minGiorno, 60), $minGiorno % 60) : $entrata;
                    $sheet->setCellValue($col . $row, $oreStr);
                } else {
                    $sheet->setCellValue($col . $row, '-');
                    $sheet->getStyle($col . $row)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('CCCCCC'));
                }
                $col++;
            }

            // Totale ore
            $sheet->setCellValue($colTot . $row, sprintf('%dh %02dm', intdiv($totMinuti, 60), $totMinuti % 60));
            $sheet->getStyle($colTot . $row)->getFont()->setBold(true);

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F4FF');
            }

            $row++;
        }

        $lastRow = $row - 1;
        if ($lastRow >= 4) {
            $sheet->getStyle("A4:{$colTot}{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'font' => ['size' => 9],
            ]);
            $sheet->getStyle("A4:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("A4:A{$lastRow}")->getFont()->setSize(10);
        }
    }
}
