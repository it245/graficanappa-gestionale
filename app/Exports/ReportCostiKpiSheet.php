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

class ReportCostiKpiSheet implements FromArray, WithHeadings, WithTitle, WithStyles, WithColumnWidths
{
    private $kpi;
    private $kpiPrev;

    public function __construct($kpi, $kpiPrev)
    {
        $this->kpi = $kpi;
        $this->kpiPrev = $kpiPrev;
    }

    public function title(): string
    {
        return 'KPI';
    }

    public function headings(): array
    {
        return ['KPI', 'Periodo Attuale', 'Periodo Precedente', 'Delta'];
    }

    public function array(): array
    {
        $k = $this->kpi;
        $p = $this->kpiPrev;

        $d = fn($curr, $prev) => $prev > 0 ? round((($curr - $prev) / $prev) * 100, 1) . '%' : '-';

        return [
            ['Valore Vendita', '€' . number_format($k->totaleValore, 2), '€' . number_format($p->totaleValore, 2), $d($k->totaleValore, $p->totaleValore)],
            ['Costo Lavorazione', '€' . number_format($k->costoTotaleLav, 2), '€' . number_format($p->costoTotaleLav, 2), $d($k->costoTotaleLav, $p->costoTotaleLav)],
            ['Costo Materiali', '€' . number_format($k->costoTotaleMat, 2), '€' . number_format($p->costoTotaleMat, 2), $d($k->costoTotaleMat, $p->costoTotaleMat)],
            ['Margine Lordo', '€' . number_format($k->margineTotal, 2), '€' . number_format($p->margineTotal, 2), $p->margineTotal != 0 ? round((($k->margineTotal - $p->margineTotal) / abs($p->margineTotal)) * 100, 1) . '%' : '-'],
            ['Margine % Medio', $k->marginePercMedio . '%', $p->marginePercMedio . '%', round($k->marginePercMedio - $p->marginePercMedio, 1) . ' pp'],
            ['Commesse Analizzate', $k->numCommesse, $p->numCommesse, $d($k->numCommesse, $p->numCommesse)],
            ['Commesse in Perdita', $k->commesseInPerdita, $p->commesseInPerdita, $d($k->commesseInPerdita, $p->commesseInPerdita)],
            ['Costo Medio Commessa', '€' . number_format($k->costoMedioCommessa, 2), '€' . number_format($p->costoMedioCommessa, 2), $d($k->costoMedioCommessa, $p->costoMedioCommessa)],
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 25, 'B' => 22, 'C' => 22, 'D' => 15];
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
