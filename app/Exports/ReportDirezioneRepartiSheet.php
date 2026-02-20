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

class ReportDirezioneRepartiSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    private $colliBottiglia;

    public function __construct($colliBottiglia)
    {
        $this->colliBottiglia = $colliBottiglia;
    }

    public function title(): string
    {
        return 'Reparti';
    }

    public function headings(): array
    {
        return ['Reparto', 'Fasi in Coda', 'Fasi in Corso', 'Completate Periodo', 'Tempo Medio (min)', 'Indice Bottleneck'];
    }

    public function array(): array
    {
        return $this->colliBottiglia->map(function ($r) {
            return [
                $r->nome,
                $r->coda,
                $r->in_corso,
                $r->completate_periodo,
                round($r->tempo_medio_sec / 60, 1),
                $r->indice_bottleneck,
            ];
        })->toArray();
    }

    public function columnWidths(): array
    {
        return ['A' => 22, 'B' => 14, 'C' => 14, 'D' => 18, 'E' => 18, 'F' => 18];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '000000']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        return [];
    }
}
