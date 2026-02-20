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

class ReportDirezioneRitardiSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    private $ritardi;

    public function __construct($ritardi)
    {
        $this->ritardi = $ritardi;
    }

    public function title(): string
    {
        return 'Ritardi';
    }

    public function headings(): array
    {
        return ['Commessa', 'Cliente', 'Data Consegna', 'Giorni Ritardo', 'Avanzamento %', 'Fasi Mancanti'];
    }

    public function array(): array
    {
        return $this->ritardi->map(function ($r) {
            return [
                $r->commessa,
                $r->cliente_nome ?? '-',
                $r->data_prevista_consegna ? Carbon::parse($r->data_prevista_consegna)->format('d/m/Y') : '-',
                $r->giorni_ritardo,
                $r->avanzamento . '%',
                $r->fasi_mancanti ?? '-',
            ];
        })->toArray();
    }

    public function columnWidths(): array
    {
        return ['A' => 16, 'B' => 22, 'C' => 16, 'D' => 16, 'E' => 14, 'F' => 40];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '000000']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        // Highlight all data rows in light red
        if ($lastRow > 1) {
            $sheet->getStyle("A2:F{$lastRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FDE8E8']],
            ]);
        }
        return [];
    }
}
