<?php

namespace App\Exports;

use App\Models\OrdineFase;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DashboardMesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function collection()
    {
        return OrdineFase::with(['ordine', 'faseCatalogo.reparto', 'operatori' => fn($q) => $q->select('operatori.id', 'nome')])
            ->where('stato', '<', 3)
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID', 'Commessa', 'Stato', 'Cliente', 'Cod Articolo', 'Descrizione',
            'Qta', 'UM', 'Priorita', 'Data Registrazione', 'Data Prevista Consegna',
            'Cod Carta', 'Carta', 'Qta Carta', 'UM Carta', 'Note Prestampa', 'Responsabile', 'Commento Produzione',
            'Fase', 'Reparto', 'Operatori',
            'Qta Prod', 'Note', 'Data Inizio', 'Data Fine',
        ];
    }

    public function map($fase): array
    {
        $ordine = $fase->ordine;

        // Data inizio: dalla pivot operatore, fallback dal campo ordine_fasi
        $dataInizio = null;
        if ($fase->operatori->isNotEmpty()) {
            $primo = $fase->operatori->sortBy('pivot.data_inizio')->first();
            $dataInizio = $primo?->pivot->data_inizio;
        }
        if (!$dataInizio) {
            $dataInizio = $fase->getAttributes()['data_inizio'] ?? null;
        }
        $dataInizio = $dataInizio ? Carbon::parse($dataInizio)->format('d/m/Y H:i:s') : '';

        // Data fine
        $dataFine = $fase->getAttributes()['data_fine'] ?? null;
        $dataFine = $dataFine ? Carbon::parse($dataFine)->format('d/m/Y H:i:s') : '';

        // Operatori
        $operatoriNomi = $fase->operatori->pluck('nome')->implode(', ');

        return [
            $fase->id,
            $ordine->commessa ?? '',
            $fase->stato,
            $ordine->cliente_nome ?? '',
            $ordine->cod_art ?? '',
            $ordine->descrizione ?? '',
            $ordine->qta_richiesta ?? '',
            $ordine->um ?? '',
            $fase->priorita ?? '',
            $ordine->data_registrazione ? Carbon::parse($ordine->data_registrazione)->format('d/m/Y') : '',
            $ordine->data_prevista_consegna ? Carbon::parse($ordine->data_prevista_consegna)->format('d/m/Y') : '',
            $ordine->cod_carta ?? '',
            $ordine->carta ?? '',
            $ordine->qta_carta ?? '',
            $ordine->UM_carta ?? '',
            $ordine->note_prestampa ?? '',
            $ordine->responsabile ?? '',
            $ordine->commento_produzione ?? '',
            $fase->faseCatalogo->nome ?? $fase->fase ?? '',
            $fase->faseCatalogo->reparto->nome ?? '',
            $operatoriNomi,
            $fase->qta_prod ?? '',
            $fase->note ?? '',
            $dataInizio,
            $dataFine,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 14,  // Commessa
            'C' => 8,   // Stato
            'D' => 20,  // Cliente
            'E' => 14,  // Cod Articolo
            'F' => 30,  // Descrizione
            'G' => 10,  // Qta
            'H' => 6,   // UM
            'I' => 10,  // Priorita
            'J' => 16,  // Data Registrazione
            'K' => 20,  // Data Prevista Consegna
            'L' => 12,  // Cod Carta
            'M' => 20,  // Carta
            'N' => 10,  // Qta Carta
            'O' => 10,  // UM Carta
            'P' => 30,  // Note Prestampa
            'Q' => 20,  // Responsabile
            'R' => 30,  // Commento Produzione
            'S' => 22,  // Fase
            'T' => 14,  // Reparto
            'U' => 20,  // Operatori
            'V' => 10,  // Qta Prod
            'W' => 25,  // Note
            'X' => 18,  // Data Inizio
            'Y' => 18,  // Data Fine
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $nonEditabili = ['A', 'B', 'S', 'T', 'U'];

        // Header: sfondo nero, testo bianco, grassetto
        $sheet->getStyle('A1:Y1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Colonne non editabili: sfondo grigio
        if ($lastRow > 1) {
            foreach ($nonEditabili as $col) {
                $sheet->getStyle("{$col}2:{$col}{$lastRow}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9D9D9'],
                    ],
                ]);
            }
        }

        // Auto-filtro sulla riga header
        $sheet->setAutoFilter('A1:Y1');

        return [];
    }
}
