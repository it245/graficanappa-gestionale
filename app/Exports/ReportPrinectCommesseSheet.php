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

class ReportPrinectCommesseSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    private $commesse;

    public function __construct($commesse) { $this->commesse = $commesse; }

    public function title(): string { return 'Commesse'; }

    public function headings(): array
    {
        return ['Commessa', 'Job', 'Fogli Buoni', 'Fogli Scarto', 'Scarto %', 'Ore Avv.', 'Ore Prod.', 'N. Attivita'];
    }

    public function array(): array
    {
        return $this->commesse->map(function ($c) {
            return [
                $c->commessa,
                $c->job_name,
                $c->buoni,
                $c->scarto,
                $c->scarto_pct . '%',
                round($c->sec_avv / 3600, 1),
                round($c->sec_prod / 3600, 1),
                $c->n_attivita,
            ];
        })->toArray();
    }

    public function columnWidths(): array
    {
        return ['A' => 16, 'B' => 30, 'C' => 14, 'D' => 14, 'E' => 12, 'F' => 12, 'G' => 12, 'H' => 12];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '000000']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        return [];
    }
}
