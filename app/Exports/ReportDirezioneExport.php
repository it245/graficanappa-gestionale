<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReportDirezioneExport implements WithMultipleSheets
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
            'KPI' => new ReportDirezioneKpiSheet($this->kpi, $this->kpiPrev),
            'Reparti' => new ReportDirezioneRepartiSheet($this->kpi->colliBottiglia),
            'Operatori' => new ReportDirezioneOperatoriSheet($this->kpi->operatoriPerf),
            'Commesse' => new ReportDirezioneCommesseSheet($this->kpi->dettaglioCompletate),
            'Ritardi' => new ReportDirezioneRitardiSheet($this->kpi->commesseInRitardo),
        ];
    }
}
