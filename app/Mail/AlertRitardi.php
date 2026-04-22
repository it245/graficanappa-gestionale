<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AlertRitardi extends Mailable
{
    public array $scadute;
    public array $critiche;

    public function __construct(array $scadute, array $critiche)
    {
        $this->scadute = $scadute;
        $this->critiche = $critiche;
    }

    public function envelope(): Envelope
    {
        $tot = count($this->scadute) + count($this->critiche);
        return new Envelope(
            subject: "[MES] Alert: $tot commesse a rischio consegna",
        );
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->buildHtml());
    }

    protected function buildHtml(): string
    {
        $rowsScadute = '';
        foreach ($this->scadute as $s) {
            $rowsScadute .= "<tr><td style='padding:6px 10px;border:1px solid #fecaca'>{$s['commessa']}</td><td style='padding:6px 10px;border:1px solid #fecaca'>{$s['cliente']}</td><td style='padding:6px 10px;border:1px solid #fecaca'>{$s['fase']}</td><td style='padding:6px 10px;border:1px solid #fecaca;text-align:center'>{$s['reparto']}</td><td style='padding:6px 10px;border:1px solid #fecaca;text-align:center;color:#b91c1c;font-weight:700'>{$s['consegna']}</td><td style='padding:6px 10px;border:1px solid #fecaca;text-align:center;color:#b91c1c;font-weight:700'>{$s['gg']}gg</td></tr>";
        }
        $rowsCritiche = '';
        foreach ($this->critiche as $c) {
            $rowsCritiche .= "<tr><td style='padding:6px 10px;border:1px solid #fde68a'>{$c['commessa']}</td><td style='padding:6px 10px;border:1px solid #fde68a'>{$c['cliente']}</td><td style='padding:6px 10px;border:1px solid #fde68a'>{$c['fase']}</td><td style='padding:6px 10px;border:1px solid #fde68a;text-align:center'>{$c['reparto']}</td><td style='padding:6px 10px;border:1px solid #fde68a;text-align:center;color:#92400e;font-weight:700'>{$c['consegna']}</td><td style='padding:6px 10px;border:1px solid #fde68a;text-align:center;color:#92400e;font-weight:700'>{$c['gg']}gg</td></tr>";
        }

        $tableScadute = empty($this->scadute) ? '' : "
        <h3 style='color:#b91c1c;margin-top:24px'>Già scadute (" . count($this->scadute) . ")</h3>
        <table style='border-collapse:collapse;background:#fef2f2;width:100%'>
            <thead><tr style='background:#fecaca'><th style='padding:8px 10px;border:1px solid #fecaca;text-align:left'>Commessa</th><th style='padding:8px 10px;border:1px solid #fecaca;text-align:left'>Cliente</th><th style='padding:8px 10px;border:1px solid #fecaca;text-align:left'>Fase</th><th style='padding:8px 10px;border:1px solid #fecaca'>Reparto</th><th style='padding:8px 10px;border:1px solid #fecaca'>Consegna</th><th style='padding:8px 10px;border:1px solid #fecaca'>Ritardo</th></tr></thead>
            <tbody>$rowsScadute</tbody>
        </table>";

        $tableCritiche = empty($this->critiche) ? '' : "
        <h3 style='color:#92400e;margin-top:24px'>Critiche ≤ 2gg (" . count($this->critiche) . ")</h3>
        <table style='border-collapse:collapse;background:#fffbeb;width:100%'>
            <thead><tr style='background:#fde68a'><th style='padding:8px 10px;border:1px solid #fde68a;text-align:left'>Commessa</th><th style='padding:8px 10px;border:1px solid #fde68a;text-align:left'>Cliente</th><th style='padding:8px 10px;border:1px solid #fde68a;text-align:left'>Fase</th><th style='padding:8px 10px;border:1px solid #fde68a'>Reparto</th><th style='padding:8px 10px;border:1px solid #fde68a'>Consegna</th><th style='padding:8px 10px;border:1px solid #fde68a'>Giorni</th></tr></thead>
            <tbody>$rowsCritiche</tbody>
        </table>";

        return "
        <div style='font-family:Arial,sans-serif;max-width:900px'>
            <h2 style='color:#dc2626'>⚠ Alert commesse a rischio</h2>
            <p>Generato il " . now()->format('d/m/Y H:i') . "</p>
            $tableScadute
            $tableCritiche
            <p style='color:#9ca3af;font-size:12px;margin-top:24px'>MES Grafica Nappa — Alert automatico. Accedi alla dashboard per gestire le priorità.</p>
        </div>";
    }
}
