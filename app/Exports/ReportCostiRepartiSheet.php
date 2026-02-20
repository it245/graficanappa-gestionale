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

class ReportCostiRepartiSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    private $reparti;

    public function __construct($reparti)
    {
        $this->reparti = $reparti;
    }

    public function title(): string
    {
        return 'Reparti';
    }

    public function headings(): array
    {
        return ['Reparto', 'Ore Lavorate', 'Tariffa €/h', 'Costo Totale €'];
    }

    public function array(): array
    {
        return $this->reparti->map(function ($r) {
            return [
                $r->reparto_nome,
                $r->ore,
                $r->tariffa,
                $r->costo,
            ];
        })->toArray();
    }

    public function columnWidths(): array
    {
        return ['A' => 25, 'B' => 15, 'C' => 15, 'D' => 18];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E4057']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        return [];
    }
}
