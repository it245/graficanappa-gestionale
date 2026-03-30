<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReportPercorsoSheet implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    private string $nome;
    private string $color;
    private array $righe;

    public function __construct(string $nome, string $color, array $righe)
    {
        $this->nome = $nome;
        $this->color = $color;
        $this->righe = $righe;
    }

    public function title(): string
    {
        return $this->nome . ' (' . count($this->righe) . ')';
    }

    public function array(): array
    {
        return $this->righe;
    }

    public function headings(): array
    {
        return ['Commessa', 'Cliente', 'Cod. Articolo', 'Descrizione', 'Quantita', 'Consegna', 'Progresso', 'Fasi'];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 28,
            'C' => 20,
            'D' => 35,
            'E' => 12,
            'F' => 12,
            'G' => 12,
            'H' => 50,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $totalRows = count($this->righe) + 1; // +1 per header

        // Header
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF333333']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Colora tutte le righe dati con il colore del gruppo
        if ($totalRows > 1) {
            $sheet->getStyle('A2:H' . $totalRows)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF' . ltrim($this->color, '0')],
                ],
            ]);

            // Bordi leggeri
            $sheet->getStyle('A1:H' . $totalRows)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => 'FFD0D0D0'],
                    ],
                ],
            ]);

            // Commessa in grassetto
            $sheet->getStyle('A2:A' . $totalRows)->applyFromArray([
                'font' => ['bold' => true],
            ]);

            // Quantita allineata a destra
            $sheet->getStyle('E2:E' . $totalRows)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'numberFormat' => ['formatCode' => '#,##0'],
            ]);

            // Consegna e Progresso centrati
            $sheet->getStyle('F2:G' . $totalRows)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        // Freeze header
        $sheet->freezePane('A2');

        // Auto-filter
        $sheet->setAutoFilter('A1:H1');

        return [];
    }
}
