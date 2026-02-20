<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReportPrinectKpiSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    private $kpi;
    private $kpiPrev;

    public function __construct($kpi, $kpiPrev)
    {
        $this->kpi = $kpi;
        $this->kpiPrev = $kpiPrev;
    }

    public function title(): string { return 'KPI'; }

    public function headings(): array
    {
        return ['KPI', 'Periodo Attuale', 'Periodo Precedente', 'Delta'];
    }

    public function array(): array
    {
        $k = $this->kpi;
        $p = $this->kpiPrev;

        $d = fn($cur, $prev) => $prev > 0 ? round((($cur - $prev) / $prev) * 100, 1) . '%' : '-';

        return [
            ['Fogli Buoni', number_format($k->goodCycles), number_format($p->goodCycles), $d($k->goodCycles, $p->goodCycles)],
            ['Fogli Scarto', number_format($k->wasteCycles), number_format($p->wasteCycles), $d($k->wasteCycles, $p->wasteCycles)],
            ['Scarto %', $k->scartoPerc . '%', $p->scartoPerc . '%', round($k->scartoPerc - $p->scartoPerc, 1) . ' pp'],
            ['Ore Totali', $k->oreTotali . 'h', $p->oreTotali . 'h', $d($k->oreTotali, $p->oreTotali)],
            ['Ore Avviamento', $k->oreAvviamento . 'h', $p->oreAvviamento . 'h', $d($k->oreAvviamento, $p->oreAvviamento)],
            ['Ore Produzione', $k->oreProduzione . 'h', $p->oreProduzione . 'h', $d($k->oreProduzione, $p->oreProduzione)],
            ['Rapporto Avv/Tot %', $k->rapportoAvvProd . '%', $p->rapportoAvvProd . '%', round($k->rapportoAvvProd - $p->rapportoAvvProd, 1) . ' pp'],
            ['N. Commesse', $k->nCommesse, $p->nCommesse, $d($k->nCommesse, $p->nCommesse)],
            ['N. Attivita', number_format($k->nAttivita), number_format($p->nAttivita), $d($k->nAttivita, $p->nAttivita)],
            ['OEE %', $k->oee . '%', $p->oee . '%', round($k->oee - $p->oee, 1) . ' pp'],
            ['Disponibilita %', $k->oeeDisp . '%', $p->oeeDisp . '%', round($k->oeeDisp - $p->oeeDisp, 1) . ' pp'],
            ['Performance %', $k->oeePerf . '%', $p->oeePerf . '%', round($k->oeePerf - $p->oeePerf, 1) . ' pp'],
            ['Qualita %', $k->oeeQual . '%', $p->oeeQual . '%', round($k->oeeQual - $p->oeeQual, 1) . ' pp'],
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 25, 'B' => 20, 'C' => 20, 'D' => 15];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '000000']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        return [];
    }
}
