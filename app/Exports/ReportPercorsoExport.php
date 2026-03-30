<?php

namespace App\Exports;

use App\Models\Ordine;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ReportPercorsoExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        $ordini = Ordine::whereHas('fasi', fn($q) => $q->where('stato', '<', 4))
            ->with(['fasi.faseCatalogo'])
            ->orderBy('commessa')
            ->get();

        $gruppi = [
            'Base'     => ['color' => '00D4EDDA', 'ordini' => []],
            'Rilievi'  => ['color' => '00FFF3CD', 'ordini' => []],
            'Caldo'    => ['color' => '00F96F2A', 'ordini' => []],
            'Completo' => ['color' => '00F8D7DA', 'ordini' => []],
        ];

        $mapClasse = [
            'percorso-base'     => 'Base',
            'percorso-rilievi'  => 'Rilievi',
            'percorso-caldo'    => 'Caldo',
            'percorso-completo' => 'Completo',
        ];

        foreach ($ordini as $ordine) {
            $classe = $ordine->getPercorsoClass();
            $key = $mapClasse[$classe] ?? 'Base';
            $fasiTot = $ordine->fasi->count();
            $fasiComplete = $ordine->fasi->where('stato', '>=', 3)->count();

            $gruppi[$key]['ordini'][] = [
                $ordine->commessa,
                $ordine->cliente_nome ?? '-',
                $ordine->cod_art ?? '-',
                $ordine->descrizione ?? '-',
                $ordine->qta_richiesta ?? 0,
                $ordine->data_prevista_consegna
                    ? \Carbon\Carbon::parse($ordine->data_prevista_consegna)->format('d/m/Y')
                    : '-',
                "$fasiComplete/$fasiTot",
                $ordine->fasi->map(fn($f) => $f->faseCatalogo->nome ?? $f->fase ?? '-')->implode(', '),
            ];
        }

        $sheets = [];
        foreach ($gruppi as $nome => $g) {
            $sheets[] = new ReportPercorsoSheet($nome, $g['color'], $g['ordini']);
        }
        return $sheets;
    }
}
