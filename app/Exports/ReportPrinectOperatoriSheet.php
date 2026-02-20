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

class ReportPrinectOperatoriSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    private $operatori;

    public function __construct($operatori) { $this->operatori = $operatori; }

    public function title(): string { return 'Operatori'; }

    public function headings(): array
    {
        return ['Operatore', 'Fogli Buoni', 'Fogli Scarto', 'Scarto %', 'Ore Avv.', 'Ore Prod.', 'N. Attivita'];
    }

    public function array(): array
    {
        return $this->operatori->map(function ($op) {
            $tot = $op->buoni + $op->scarto;
            return [
                $op->nome,
                $op->buoni,
                $op->scarto,
                $tot > 0 ? round(($op->scarto / $tot) * 100, 1) . '%' : '0%',
                round($op->sec_avv / 3600, 1),
                round($op->sec_prod / 3600, 1),
                $op->n_attivita,
            ];
        })->toArray();
    }

    public function columnWidths(): array
    {
        return ['A' => 25, 'B' => 14, 'C' => 14, 'D' => 12, 'E' => 12, 'F' => 12, 'G' => 12];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '000000']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        return [];
    }
}
