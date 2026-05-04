<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ContatoreStampante;
use App\Models\FieryAccounting;
use Illuminate\Support\Facades\DB;
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
        {--mese-corrente : Auto: snapshot 1° del mese → oggi, etichetta mese corrente}
        {--sottrai-data=* : Date Y-m-d da sottrarre dal delta (usa FieryAccounting). Ripetibile.}';

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

        // Sottrazione giorni specifici da FieryAccounting
        $sottrai = $this->option('sottrai-data') ?: [];
        if (!empty($sottrai)) {
            $stat = FieryAccounting::whereIn('data_stampa', $sottrai)
                ->select(
                    DB::raw("SUM(CASE WHEN tipo_formato='grande' THEN pagine_colore ELSE 0 END) as col_grande"),
                    DB::raw("SUM(CASE WHEN tipo_formato='piccolo' THEN pagine_colore ELSE 0 END) as col_piccolo"),
                    DB::raw("SUM(CASE WHEN tipo_formato='grande' THEN pagine_bn ELSE 0 END) as bn_grande"),
                    DB::raw("SUM(CASE WHEN tipo_formato='piccolo' THEN pagine_bn ELSE 0 END) as bn_piccolo")
                )->first();
            $delta['colore_a3'] = max(0, $delta['colore_a3'] - (int) ($stat->col_grande ?? 0));
            $delta['colore_a4'] = max(0, $delta['colore_a4'] - (int) ($stat->col_piccolo ?? 0));
            $delta['bn_a3']     = max(0, $delta['bn_a3']     - (int) ($stat->bn_grande ?? 0));
            $delta['bn_a4']     = max(0, $delta['bn_a4']     - (int) ($stat->bn_piccolo ?? 0));
            $this->info("Sottratti " . array_sum([(int)$stat->col_grande,(int)$stat->col_piccolo,(int)$stat->bn_grande,(int)$stat->bn_piccolo])
                . " scatti dei giorni: " . implode(',', $sottrai));
        }

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
        $s->mergeCells('A1:B1');
        $s->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $s->getRowDimension(1)->setRowHeight(28);

        $s->setCellValue('A2', $mese);
        $s->mergeCells('A2:B2');
        $s->getStyle('A2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Tabella semplificata: solo Contatore | Scatti
        $s->setCellValue('A4', 'Contatore');
        $s->setCellValue('B4', 'Scatti');

        $s->getStyle('A4:B4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $rows = [
            ['B/N A4',     $delta['bn_a4']],
            ['Colore A4',  $delta['colore_a4']],
            ['B/N A3',     $delta['bn_a3']],
            ['Colore A3',  $delta['colore_a3']],
            ['Banner',     $delta['banner']],
        ];

        $row = 5;
        foreach ($rows as $r) {
            $s->setCellValue("A{$row}", $r[0]);
            $s->setCellValue("B{$row}", $r[1]);
            $s->getStyle("B{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $s->getStyle("B{$row}")->getFont()->setBold(true);
            $row++;
        }

        // Totale
        $s->setCellValue("A{$row}", 'TOTALE');
        $s->setCellValue("B{$row}", $totale);
        $s->getStyle("B{$row}")->getNumberFormat()->setFormatCode('#,##0');
        $s->getStyle("A{$row}:B{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1FAE5']],
        ]);

        // Borders
        $lastRow = $row;
        $s->getStyle("A4:B{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']],
            ],
        ]);


        // Column widths
        $s->getColumnDimension('A')->setWidth(22);
        $s->getColumnDimension('B')->setWidth(35);

        // Page setup A4 verticale, centrato, fit-to-page
        $ps = $s->getPageSetup();
        $ps->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $ps->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $ps->setFitToWidth(1);
        $ps->setFitToHeight(1);
        $ps->setHorizontalCentered(true);
        $s->getPageMargins()->setTop(0.75)->setBottom(0.75)->setLeft(0.5)->setRight(0.5);
        $ps->setPrintArea("A1:B{$lastRow}");

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
