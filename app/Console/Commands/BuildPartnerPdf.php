<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Barryvdh\DomPDF\Facade\Pdf;
use League\CommonMark\GithubFlavoredMarkdownConverter;

class BuildPartnerPdf extends Command
{
    protected $signature = 'docs:partner-pdf
                            {--out=docs/partner/MES_GraficaNappa_Partner.pdf : Output path}';

    protected $description = 'Genera PDF unico documentazione partner da docs/partner/docs/*.md';

    public function handle(): int
    {
        ini_set('memory_limit', '1G');

        $dir = base_path('docs/partner/docs');
        $readmePath = base_path('docs/partner/README.md');
        $outRel = $this->option('out');
        $outAbs = base_path($outRel);

        if (! is_dir($dir)) {
            $this->error("Directory non trovata: {$dir}");
            return 1;
        }

        $files = collect(glob($dir.'/*.md'))->sort()->values()->all();
        if (empty($files)) {
            $this->error("Nessun file .md in {$dir}");
            return 1;
        }

        $this->info("Trovati ".count($files)." file MD + README");

        // Concatena: README → 01..10
        $md = '';
        if (file_exists($readmePath)) {
            $md .= file_get_contents($readmePath)."\n\n<div style=\"page-break-after: always;\"></div>\n\n";
        }
        foreach ($files as $f) {
            $md .= file_get_contents($f)."\n\n<div style=\"page-break-after: always;\"></div>\n\n";
        }

        $this->info("Conversione CommonMark → HTML...");
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);
        $bodyHtml = (string) $converter->convert($md);

        $css = <<<'CSS'
        @page { margin: 22mm 18mm 22mm 18mm; }
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 10.5pt; color: #1f2937; line-height: 1.45; }
        h1 { font-size: 22pt; color: #2563eb; border-bottom: 2px solid #2563eb; padding-bottom: 4px; margin-top: 0; }
        h2 { font-size: 16pt; color: #1e40af; margin-top: 18px; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; }
        h3 { font-size: 13pt; color: #1e3a8a; margin-top: 14px; }
        h4 { font-size: 11.5pt; color: #1e3a8a; margin-top: 10px; }
        p { margin: 6px 0; }
        code { font-family: DejaVu Sans Mono, Courier, monospace; background: #f1f5f9; padding: 1px 4px; border-radius: 3px; font-size: 9.5pt; color: #be123c; }
        pre { background: #0f172a; color: #e2e8f0; padding: 10px; border-radius: 4px; font-size: 9pt; overflow-x: auto; page-break-inside: avoid; }
        pre code { background: transparent; color: inherit; padding: 0; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; page-break-inside: avoid; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 7px; text-align: left; font-size: 9.5pt; }
        th { background: #e0e7ff; color: #1e3a8a; }
        tr:nth-child(even) td { background: #f8fafc; }
        ul, ol { margin: 6px 0 6px 20px; }
        blockquote { border-left: 3px solid #2563eb; padding: 4px 10px; background: #eff6ff; color: #1e40af; margin: 8px 0; }
        a { color: #2563eb; text-decoration: none; }
        hr { border: 0; border-top: 1px solid #cbd5e1; margin: 14px 0; }
        .cover { text-align: center; padding: 80mm 0 0; page-break-after: always; }
        .cover h1 { font-size: 28pt; border: 0; }
        .cover .subtitle { font-size: 14pt; color: #64748b; margin-top: 14px; }
        .cover .footer { margin-top: 80mm; font-size: 10pt; color: #64748b; }
        CSS;

        $cover = <<<HTML
        <div class="cover">
            <h1>Mossa 37</h1>
            <div class="subtitle">Documentazione Tecnica</div>
            <div class="footer">
                © 2025-2026 Grafica Nappa S.r.l. — Tutti i diritti riservati.<br>
                <em>Documento riservato. Distribuzione limitata ai partner autorizzati.</em>
            </div>
        </div>
        HTML;
        $cover = str_replace('{date}', now()->format('d/m/Y'), $cover);

        $html = "<!DOCTYPE html><html lang=\"it\"><head><meta charset=\"UTF-8\"><style>{$css}</style></head><body>{$cover}{$bodyHtml}</body></html>";

        $this->info("Rendering PDF (dompdf)...");
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'defaultFont' => 'DejaVu Sans',
        ]);

        @mkdir(dirname($outAbs), 0755, true);
        file_put_contents($outAbs, $pdf->output());

        $sizeKb = round(filesize($outAbs) / 1024, 1);
        $this->info("PDF generato: {$outAbs} ({$sizeKb} KB)");
        return 0;
    }
}
