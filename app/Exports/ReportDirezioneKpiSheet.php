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

class ReportDirezioneKpiSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    private $kpi;
    private $kpiPrev;

    public function __construct($kpi, $kpiPrev)
    {
        $this->kpi = $kpi;
        $this->kpiPrev = $kpiPrev;
    }

    public function title(): string
    {
        return 'KPI';
    }

    public function headings(): array
    {
        return ['KPI', 'Periodo Attuale', 'Periodo Precedente', 'Delta %'];
    }

    public function array(): array
    {
        $k = $this->kpi;
        $p = $this->kpiPrev;

        $deltaFasi = $p->fasiCompletate > 0 ? round((($k->fasiCompletate - $p->fasiCompletate) / $p->fasiCompletate) * 100, 1) . '%' : '-';
        $deltaOre = $p->oreLavorate > 0 ? round((($k->oreLavorate - $p->oreLavorate) / $p->oreLavorate) * 100, 1) . '%' : '-';
        $deltaComm = $p->numCommesseCompletate > 0 ? round((($k->numCommesseCompletate - $p->numCommesseCompletate) / $p->numCommesseCompletate) * 100, 1) . '%' : '-';
        $deltaRit = $p->numCommesseInRitardo > 0 ? round((($k->numCommesseInRitardo - $p->numCommesseInRitardo) / $p->numCommesseInRitardo) * 100, 1) . '%' : '-';
        $deltaPunt = round($k->tassoPuntualita - $p->tassoPuntualita, 1) . ' pp';
        $deltaScarto = round($k->scartoPercentuale - $p->scartoPercentuale, 1) . ' pp';

        return [
            ['Fasi Completate', $k->fasiCompletate, $p->fasiCompletate, $deltaFasi],
            ['Ore Lavorate', $k->oreLavorate, $p->oreLavorate, $deltaOre],
            ['Commesse Completate', $k->numCommesseCompletate, $p->numCommesseCompletate, $deltaComm],
            ['Commesse in Ritardo', $k->numCommesseInRitardo, $p->numCommesseInRitardo, $deltaRit],
            ['Tasso Puntualita %', $k->tassoPuntualita . '%', $p->tassoPuntualita . '%', $deltaPunt],
            ['Scarto Prinect %', $k->scartoPercentuale . '%', $p->scartoPercentuale . '%', $deltaScarto],
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
