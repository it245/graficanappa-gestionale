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

class ReportCostiCommesseSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
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
        return ['Commessa', 'Cliente', 'Descrizione', 'Valore Ordine', 'Costo Lav.', 'Costo Mat.', 'Costo Totale', 'Margine €', 'Margine %', 'Ore Stimate', 'Ore Effettive', 'Δ Ore %', 'Consegna'];
    }

    public function array(): array
    {
        return $this->commesse->map(function ($c) {
            return [
                $c->commessa,
                $c->cliente,
                \Illuminate\Support\Str::limit($c->descrizione, 40),
                $c->valore_ordine > 0 ? $c->valore_ordine : '',
                $c->costo_lav,
                $c->costo_materiali > 0 ? $c->costo_materiali : '',
                $c->costo_totale,
                $c->margine ?? '',
                $c->margine_pct !== null ? $c->margine_pct . '%' : '',
                $c->ore_stimate > 0 ? $c->ore_stimate : '',
                $c->ore_effettive,
                $c->delta_ore_pct !== null ? $c->delta_ore_pct . '%' : '',
                $c->data_consegna ?? '',
            ];
        })->toArray();
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16, 'B' => 22, 'C' => 30, 'D' => 14, 'E' => 12,
            'F' => 12, 'G' => 14, 'H' => 12, 'I' => 10, 'J' => 11,
            'K' => 11, 'L' => 10, 'M' => 12,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E4057']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Colora in rosso le righe con margine negativo
        $row = 2;
        foreach ($this->commesse as $c) {
            if ($c->margine !== null && $c->margine < 0) {
                $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFCCCC']],
                ]);
            }
            $row++;
        }

        return [];
    }
}
