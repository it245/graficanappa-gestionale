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
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'RIEPILOGO PRESENZE — ' . $dataInizio->format('d/m/Y') . ' → ' . $oggi->format('d/m/Y'));
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'D11317']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(35);

        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', 'Aggiornato: ' . now()->format('d/m/Y H:i'));
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '666666']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Header
        $headers = ['Dipendente', 'Matricola', 'Giorni Presenti', 'Ore Totali', 'Media Ore/Giorno', 'Media Entrata', 'Media Uscita', 'Ultimo Giorno'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '3', $h);
            $col++;
        }
        $sheet->getStyle('A3:H3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension(3)->setRowHeight(30);

        // Dati riepilogo per dipendente
        $row = 4;
        foreach ($anagrafica as $anag) {
            // Giorni con almeno una timbratura E
            $giorniPresente = DB::table('nettime_timbrature')
                ->where('matricola', $anag->matricola)
                ->where('verso', 'E')
                ->where('data_ora', '>=', $dataInizio->format('Y-m-d'))
                ->selectRaw('COUNT(DISTINCT DATE(data_ora)) as giorni')
                ->value('giorni');

            if ($giorniPresente == 0) continue;

            // Ore totali: somma intervalli E→U
            $timbrature = DB::table('nettime_timbrature')
                ->where('matricola', $anag->matricola)
                ->where('data_ora', '>=', $dataInizio->format('Y-m-d'))
                ->orderBy('data_ora')
                ->get();

            $oreTotali = 0;
            $entrateOre = [];
            $usciteOre = [];
            $entr = null;
            foreach ($timbrature as $t) {
                if ($t->verso === 'E') {
                    $entr = Carbon::parse($t->data_ora);
                    $entrateOre[] = (int) $entr->format('H') * 60 + (int) $entr->format('i');
                } elseif ($t->verso === 'U' && $entr) {
                    $usc = Carbon::parse($t->data_ora);
                    $oreTotali += $entr->diffInMinutes($usc);
                    $usciteOre[] = (int) $usc->format('H') * 60 + (int) $usc->format('i');
                    $entr = null;
                }
            }

            $mediaOre = $giorniPresente > 0 ? $oreTotali / $giorniPresente : 0;
            $mediaEntrata = !empty($entrateOre) ? array_sum($entrateOre) / count($entrateOre) : 0;
            $mediaUscita = !empty($usciteOre) ? array_sum($usciteOre) / count($usciteOre) : 0;

            $ultimoGiorno = DB::table('nettime_timbrature')
                ->where('matricola', $anag->matricola)
                ->where('verso', 'E')
                ->max('data_ora');

            $sheet->setCellValue('A' . $row, "{$anag->cognome} {$anag->nome}");
            $sheet->setCellValueExplicit('B' . $row, $anag->matricola, DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $row, $giorniPresente);
            $sheet->setCellValue('D' . $row, sprintf('%dh %02dm', intdiv($oreTotali, 60), $oreTotali % 60));
            $sheet->setCellValue('E' . $row, sprintf('%dh %02dm', intdiv((int)$mediaOre, 60), ((int)$mediaOre) % 60));
            $sheet->setCellValue('F' . $row, sprintf('%d:%02d', intdiv((int)$mediaEntrata, 60), ((int)$mediaEntrata) % 60));
            $sheet->setCellValue('G' . $row, !empty($usciteOre) ? sprintf('%d:%02d', intdiv((int)$mediaUscita, 60), ((int)$mediaUscita) % 60) : '-');
            $sheet->setCellValue('H' . $row, $ultimoGiorno ? Carbon::parse($ultimoGiorno)->format('d/m/Y') : '-');

            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:H{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F4FF');
            }

            $row++;
        }

        $lastRow = $row - 1;
        if ($lastRow >= 4) {
            $sheet->getStyle("A4:H{$lastRow}")->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'font' => ['size' => 11],
            ]);
            $sheet->getStyle("C4:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D4:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Larghezze
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(16);
    }
}
