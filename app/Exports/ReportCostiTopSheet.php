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

class ReportCostiTopSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    private $profittevoli;
    private $perdita;

    public function __construct($profittevoli, $perdita)
    {
        $this->profittevoli = $profittevoli;
        $this->perdita = $perdita;
    }

    public function title(): string
    {
        return 'Top e Perdita';
    }

    public function headings(): array
    {
        return ['Commessa', 'Cliente', 'Valore Ordine', 'Costo Totale', 'Margine €', 'Margine %'];
    }

    public function array(): array
    {
        $rows = [];

        // Top profittevoli
        $rows[] = ['--- TOP PROFITTEVOLI ---', '', '', '', '', ''];
        foreach ($this->profittevoli as $c) {
            $rows[] = [
                $c->commessa,
                $c->cliente,
                $c->valore_ordine,
                $c->costo_totale,
                $c->margine,
                $c->margine_pct !== null ? $c->margine_pct . '%' : '',
            ];
        }

        $rows[] = ['', '', '', '', '', ''];
        $rows[] = ['--- COMMESSE IN PERDITA ---', '', '', '', '', ''];

        foreach ($this->perdita as $c) {
            $rows[] = [
                $c->commessa,
                $c->cliente,
                $c->valore_ordine,
                $c->costo_totale,
                $c->margine,
                $c->margine_pct !== null ? $c->margine_pct . '%' : '',
            ];
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return ['A' => 20, 'B' => 25, 'C' => 16, 'D' => 16, 'E' => 14, 'F' => 12];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E4057']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Colora label separatore
        $row = 2; // "TOP PROFITTEVOLI"
        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4EDDA']],
        ]);

        $perditaStart = 2 + $this->profittevoli->count() + 2; // skip label + rows + blank + label
        $sheet->getStyle("A{$perditaStart}:F{$perditaStart}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8D7DA']],
        ]);

        // Colora righe in perdita in rosso
        for ($i = $perditaStart + 1; $i <= $perditaStart + $this->perdita->count(); $i++) {
            $sheet->getStyle("A{$i}:F{$i}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFCCCC']],
            ]);
        }

        return [];
    }
}
