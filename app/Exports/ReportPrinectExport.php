<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReportPrinectExport implements WithMultipleSheets
{
    private $kpi;
    private $kpiPrev;
    private $periodo;

    public function __construct($kpi, $kpiPrev, $periodo)
    {
        $this->kpi = $kpi;
        $this->kpiPrev = $kpiPrev;
        $this->periodo = $periodo;
    }

    public function sheets(): array
    {
        return [
            'KPI' => new ReportPrinectKpiSheet($this->kpi, $this->kpiPrev),
            'Commesse' => new ReportPrinectCommesseSheet($this->kpi->perCommessa),
            'Operatori' => new ReportPrinectOperatoriSheet($this->kpi->perOperatore),
        ];
    }
}
