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

class ReportDirezioneOperatoriSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    private $operatori;

    public function __construct($operatori)
    {
        $this->operatori = $operatori;
    }

    public function title(): string
    {
        return 'Operatori';
    }

    public function headings(): array
    {
        return ['Operatore', 'Reparti', 'Fasi Completate', 'Ore Lavorate', 'Qta Prodotta', 'Tempo Medio (min)', 'Fasi/Giorno'];
    }

    public function array(): array
    {
        return $this->operatori->map(function ($op) {
            return [
                $op->nome . ' ' . $op->cognome,
                $op->reparti,
                $op->fasi_completate,
                $op->ore_lavorate,
                $op->qta_prodotta ?? 0,
                round($op->tempo_medio_sec / 60, 1),
                $op->fasi_giorno,
            ];
        })->toArray();
    }

    public function columnWidths(): array
    {
        return ['A' => 22, 'B' => 22, 'C' => 16, 'D' => 14, 'E' => 14, 'F' => 18, 'G' => 14];
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
