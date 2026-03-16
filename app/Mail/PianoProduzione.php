<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PianoProduzione extends Mailable
{
    public string $filePath;
    public array $riepilogo;

    public function __construct(string $filePath, array $riepilogo)
    {
        $this->filePath = $filePath;
        $this->riepilogo = $riepilogo;
    }

    public function envelope(): Envelope
    {
        $data = now()->format('d/m/Y');
        return new Envelope(
            subject: "Piano Produzione Mossa 37 — $data",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    public function attachments(): array
    {
        return [
            \Illuminate\Mail\Mailables\Attachment::fromPath($this->filePath)
                ->as('piano_produzione_' . now()->format('Y-m-d') . '.xlsx')
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        ];
    }

    protected function buildHtml(): string
    {
        $r = $this->riepilogo;
        $rows = '';
        foreach ($r['per_macchina'] ?? [] as $mac => $cnt) {
            $rows .= "<tr><td style='padding:4px 12px;border:1px solid #e5e7eb'>$mac</td><td style='padding:4px 12px;border:1px solid #e5e7eb;text-align:center'>$cnt</td></tr>";
        }

        return "
        <div style='font-family:Arial,sans-serif;max-width:600px'>
            <h2 style='color:#059669'>Piano Produzione — Mossa 37</h2>
            <p>Generato il " . now()->format('d/m/Y H:i') . "</p>
            <table style='border-collapse:collapse;margin:16px 0'>
                <tr><td style='padding:4px 12px'><strong>Fasi caricate:</strong></td><td>{$r['fasi']}</td></tr>
                <tr><td style='padding:4px 12px'><strong>Fasi schedulate:</strong></td><td>{$r['schedulate']}</td></tr>
                <tr><td style='padding:4px 12px'><strong>Fasi propagate:</strong></td><td>" . ($r['propagate'] ?? 0) . "</td></tr>
            </table>
            <h3>Per macchina</h3>
            <table style='border-collapse:collapse'>
                <tr style='background:#f3f4f6'><th style='padding:4px 12px;border:1px solid #e5e7eb'>Macchina</th><th style='padding:4px 12px;border:1px solid #e5e7eb'>Fasi</th></tr>
                $rows
            </table>
            <p style='color:#9ca3af;font-size:12px;margin-top:20px'>Piano in allegato (Excel con foglio per ogni macchina)</p>
        </div>";
    }
}
