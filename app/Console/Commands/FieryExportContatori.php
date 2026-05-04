<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ContatoreStampante;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\Mail;

class FieryExportContatori extends Command
{
    protected $signature = 'fiery:export-contatori
        {--inizio= : ID snapshot iniziale (oppure data Y-m-d)}
        {--fine= : ID snapshot finale (oppure data Y-m-d, default: ultimo)}
        {--mese= : Etichetta periodo (es. APRILE 2026)}
        {--giorni-effettivi= : Numero giorni effettivi da fatturare (scala delta proporzionale, es. 30 per aprile)}
        {--email= : Indirizzi email separati da virgola (invia XLSX in allegato)}
        {--mese-corrente : Auto: snapshot 1° del mese → oggi, etichetta mese corrente}';

    protected $description = 'Esporta XLSX consumi Canon iPR V900 (delta tra 2 snapshot)';

    public function handle(): int
    {
        // Modalità auto-mese: cerca snapshot più vicino al 1° del mese e ultimo
        if ($this->option('mese-corrente')) {
            $primoMese = now()->startOfMonth()->toDateString();
            $inizio = ContatoreStampante::where('stampante', 'Canon iPR V900')
                ->whereDate('rilevato_at', '<=', $primoMese)
                ->orderByDesc('rilevato_at')->first();
            $fine = ContatoreStampante::where('stampante', 'Canon iPR V900')
                ->orderByDesc('rilevato_at')->first();
            if (!$inizio || !$fine) {
                $this->error('Snapshot inizio/fine non trovati');
                return 1;
            }
            $meseLabel = strtoupper(now()->locale('it')->translatedFormat('F Y'));
            $this->generaEInvia($inizio, $fine, $meseLabel);
            return 0;
        }

        $inizio = $this->resolveSnapshot($this->option('inizio'));
        if (!$inizio) {
            $this->error('Snapshot iniziale non trovato');
            return 1;
        }

        $fineOpt = $this->option('fine');
        if ($fineOpt) {
            $fine = $this->resolveSnapshot($fineOpt);
        } else {
            $fine = ContatoreStampante::where('stampante', 'Canon iPR V900')
                ->orderByDesc('rilevato_at')->first();
        }
        if (!$fine) {
            $this->error('Snapshot finale non trovato');
            return 1;
        }

        $delta = [
            'bn_a4'     => max(0, $fine->nero_piccolo  - $inizio->nero_piccolo),
            'colore_a4' => max(0, $fine->colore_piccolo - $inizio->colore_piccolo),
            'bn_a3'     => max(0, $fine->nero_grande   - $inizio->nero_grande),
            'colore_a3' => max(0, $fine->colore_grande - $inizio->colore_grande),
            'banner'    => max(0, $fine->foglio_lungo  - $inizio->foglio_lungo),
        ];

        // Scaling proporzionale (esclude giorni extra come 04/05 e marzo 30-31)
        $giorniEff = (int) $this->option('giorni-effettivi');
        if ($giorniEff > 0) {
            $giorniTot = max(1, (int) round($inizio->rilevato_at->diffInDays($fine->rilevato_at)));
            $fattore = $giorniEff / $giorniTot;
            foreach ($delta as $k => $v) {
                $delta[$k] = (int) round($v * $fattore);
            }
            $this->info("Scaling: {$giorniEff}/{$giorniTot} giorni = " . round($fattore * 100, 1) . '%');
        }

        $totale = array_sum($delta);

        $mese = $this->option('mese') ?: 'PERIODO ' . $inizio->rilevato_at->format('d/m/Y') . ' - ' . $fine->rilevato_at->format('d/m/Y');

        $sp = new Spreadsheet();
        $s = $sp->getActiveSheet();
        $s->setTitle('Consumi V900');

        // Header
        $s->setCellValue('A1', 'CONSUMI Canon ImagePRESS V900');
        $s->mergeCells('A1:E1');
        $s->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $s->getRowDimension(1)->setRowHeight(28);

        $s->setCellValue('A2', $mese);
        $s->mergeCells('A2:E2');
        $s->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Tabella
        $s->setCellValue('A4', 'Contatore');
        $s->setCellValue('B4', 'Lettura iniziale');
        $s->setCellValue('C4', 'Lettura finale');
        $s->setCellValue('D4', 'Scatti');
        $s->setCellValue('E4', 'Note');

        $s->getStyle('A4:E4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $rows = [
            ['B/N A4',     $inizio->nero_piccolo,   $fine->nero_piccolo,   $delta['bn_a4']],
            ['Colore A4',  $inizio->colore_piccolo, $fine->colore_piccolo, $delta['colore_a4']],
            ['B/N A3',     $inizio->nero_grande,    $fine->nero_grande,    $delta['bn_a3']],
            ['Colore A3',  $inizio->colore_grande,  $fine->colore_grande,  $delta['colore_a3']],
            ['Banner',     $inizio->foglio_lungo,   $fine->foglio_lungo,   $delta['banner']],
        ];

        $row = 5;
        foreach ($rows as $r) {
            $s->setCellValue("A{$row}", $r[0]);
            $s->setCellValue("B{$row}", $r[1]);
            $s->setCellValue("C{$row}", $r[2]);
            $s->setCellValue("D{$row}", $r[3]);
            $s->getStyle("B{$row}:D{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $s->getStyle("D{$row}")->getFont()->setBold(true);
            $row++;
        }

        // Totale
        $s->setCellValue("A{$row}", 'TOTALE');
        $s->setCellValue("D{$row}", $totale);
        $s->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $s->getStyle("A{$row}:E{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1FAE5']],
        ]);

        // Borders
        $lastRow = $row;
        $s->getStyle("A4:E{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']],
            ],
        ]);

        // Snapshot info
        $row += 2;
        $s->setCellValue("A{$row}", 'Snapshot iniziale:');
        $s->setCellValue("B{$row}", $inizio->rilevato_at->format('d/m/Y H:i'));
        $row++;
        $s->setCellValue("A{$row}", 'Snapshot finale:');
        $s->setCellValue("B{$row}", $fine->rilevato_at->format('d/m/Y H:i'));
        $row++;
        $s->setCellValue("A{$row}", 'Stampante:');
        $s->setCellValue("B{$row}", $fine->stampante);

        // Column widths
        $s->getColumnDimension('A')->setWidth(20);
        $s->getColumnDimension('B')->setWidth(18);
        $s->getColumnDimension('C')->setWidth(18);
        $s->getColumnDimension('D')->setWidth(15);
        $s->getColumnDimension('E')->setWidth(25);

        // Save
        $dir = storage_path('app/exports');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = 'consumi_v900_' . $inizio->rilevato_at->format('Ymd') . '_' . $fine->rilevato_at->format('Ymd') . '.xlsx';
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        $writer = new Xlsx($sp);
        $writer->save($path);

        $this->info("Excel salvato: {$path}");
        $this->line("Totale scatti: " . number_format($totale, 0, ',', '.'));

        // Invio email opzionale
        $emailOpt = $this->option('email');
        if ($emailOpt) {
            $destinatari = array_filter(array_map('trim', explode(',', $emailOpt)));
            $body = "Report consumi Canon ImagePRESS V900\n\n"
                  . "Periodo: {$mese}\n"
                  . "Snapshot iniziale: " . $inizio->rilevato_at->format('d/m/Y H:i') . "\n"
                  . "Snapshot finale: " . $fine->rilevato_at->format('d/m/Y H:i') . "\n\n"
                  . "Totale scatti: " . number_format($totale, 0, ',', '.') . "\n\n"
                  . "Dettaglio in allegato XLSX.\n\n-- MES Grafica Nappa";
            try {
                Mail::raw($body, function ($m) use ($destinatari, $path, $mese, $filename) {
                    $m->to($destinatari)
                      ->subject("Report Consumi V900 — {$mese}")
                      ->attach($path, ['as' => $filename]);
                });
                $this->info('Email inviata a: ' . implode(', ', $destinatari));
            } catch (\Throwable $e) {
                $this->error('Errore invio email: ' . $e->getMessage());
                \Log::error('FieryExportContatori email fail', ['err' => $e->getMessage()]);
            }
        }

        return 0;
    }

    private function generaEInvia(ContatoreStampante $inizio, ContatoreStampante $fine, string $mese): void
    {
        // Wrapper per --mese-corrente: chiama handle reimpostando opzioni
        $this->input->setOption('inizio', (string) $inizio->id);
        $this->input->setOption('fine', (string) $fine->id);
        $this->input->setOption('mese', $mese);
        $this->input->setOption('mese-corrente', false);
        $this->handle();
    }

    private function resolveSnapshot(?string $val): ?ContatoreStampante
    {
        if (!$val) return null;
        if (ctype_digit($val)) {
            return ContatoreStampante::find((int) $val);
        }
        return ContatoreStampante::where('stampante', 'Canon iPR V900')
            ->whereDate('rilevato_at', $val)
            ->orderBy('rilevato_at')
            ->first();
    }
}
