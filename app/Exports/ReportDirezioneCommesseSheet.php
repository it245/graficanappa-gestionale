<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReportDirezioneCommesseSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    private $commesse;

    public function __construct($commesse)
    {
        $this->commesse = $commesse;
    }

    public function title(): string
    {
        return 'Commesse';
    }

    public function headings(): array
    {
        return ['Commessa', 'Cliente', 'Descrizione', 'Fasi Totali', 'Ore Totali', 'Data Consegna'];
    }

    public function array(): array
    {
        return $this->commesse->map(function ($c) {
            return [
                $c->commessa,
                $c->cliente,
                $c->descrizione,
                $c->fasi_totali,
                $c->ore_totali,
                $c->data_consegna ? Carbon::parse($c->data_consegna)->format('d/m/Y') : '-',
            ];
        })->toArray();
    }

    public function columnWidths(): array
    {
        return ['A' => 16, 'B' => 22, 'C' => 30, 'D' => 12, 'E' => 12, 'F' => 16];
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
