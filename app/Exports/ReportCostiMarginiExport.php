<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReportCostiMarginiExport implements WithMultipleSheets
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
            'KPI'       => new ReportCostiKpiSheet($this->kpi, $this->kpiPrev),
            'Commesse'  => new ReportCostiCommesseSheet($this->kpi->dettaglioCommesse),
            'Reparti'   => new ReportCostiRepartiSheet($this->kpi->costoPerReparto),
            'Top'       => new ReportCostiTopSheet($this->kpi->topProfittevoli, $this->kpi->topPerdita),
        ];
    }
}
