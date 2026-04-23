<?php

namespace App\Exports;

use App\Models\OrdineFase;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use App\Helpers\DescrizioneParser;

class DashboardMesExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new DashboardMesSheet('<', 3, 'tutto'),
            new DashboardMesSheet('>=', 3, 'Terminate'), // include stato 3 + 4 (consegnate)
        ];
    }
}

class DashboardMesSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    private string $operatore;
    private int $stato;
    private string $titolo;

    public function __construct(string $operatore, int $stato, string $titolo)
    {
        $this->operatore = $operatore;
        $this->stato = $stato;
        $this->titolo = $titolo;
    }

    public function title(): string
    {
        return $this->titolo;
    }

    public function collection()
    {
        return OrdineFase::with(['ordine.cliche', 'faseCatalogo.reparto', 'operatori' => fn($q) => $q->select('operatori.id', 'nome')])
            ->where('stato', $this->operatore, $this->stato)
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
            'Ordine Cliente', 'N. DDT Vendita', 'Vettore DDT', 'Qta DDT', 'Note Fasi Successive',
            'Colori', 'Fustella', 'Esterno', 'Ore Prev.', 'Ore Lav.',
            'Scarti Previsti (Onda)', 'Scarti Prinect (Macchina)', 'Scarti Reali (Operatore)', 'Cliché', 'Qta Prod. Prinect',
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

        // Nel foglio Terminate, mostra stato 4 (consegnato) come 3 (terminato)
        $statoExcel = ($this->titolo === 'Terminate' && $fase->stato == 4) ? 3 : $fase->stato;

        return [
            $fase->id,
            $ordine->commessa ?? '',
            $statoExcel,
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
            $ordine->ordine_cliente ?? '',
            $ordine->numero_ddt_vendita ?? '',
            $ordine->vettore_ddt ?? '',
            $ordine->qta_ddt_vendita ?? '',
            $ordine->note_fasi_successive ?? '',
            // Colori (calcolato da descrizione)
            DescrizioneParser::parseColori(
                $ordine->descrizione ?? '',
                $ordine->cliente_nome ?? '',
                $fase->faseCatalogo->reparto->nome ?? ''
            ),
            // Fustella (calcolato da descrizione)
            DescrizioneParser::parseFustella($ordine->descrizione ?? '', $ordine->cliente_nome ?? '', $ordine->note_prestampa ?? '') ?? '',
            // Esterno (calcolato da note "Inviato a:")
            preg_match('/Inviato a:\s*(.+)/i', $fase->note ?? '', $mEst) ? trim($mEst[1]) : '',
            // Ore Previste (calcolato da config fasi_ore)
            (function() use ($fase, $ordine) {
                $info = config('fasi_ore')[$fase->fase] ?? null;
                if (!$info) return '';
                $qtaCarta = $ordine->qta_carta ?? 0;
                $copieh = $info['copieh'] ?: 1;
                return round($info['avviamento'] + ($qtaCarta / $copieh), 1);
            })(),
            // Ore Lavorate (Prinect o pivot operatore)
            (function() use ($fase) {
                $secPrinect = ($fase->tempo_avviamento_sec ?? 0) + ($fase->tempo_esecuzione_sec ?? 0);
                if ($secPrinect > 0) return round($secPrinect / 3600, 2);
                $totSecPausa = $fase->operatori->sum(fn($op) => $op->pivot->secondi_pausa ?? 0);
                $di = $fase->operatori->whereNotNull('pivot.data_inizio')->sortBy('pivot.data_inizio')->first()?->pivot->data_inizio;
                $df = $fase->operatori->whereNotNull('pivot.data_fine')->sortByDesc('pivot.data_fine')->first()?->pivot->data_fine;
                if ($di && $df) {
                    $sec = max(abs(Carbon::parse($df)->getTimestamp() - Carbon::parse($di)->getTimestamp()) - $totSecPausa, 0);
                    return $sec > 0 ? round($sec / 3600, 2) : '';
                }
                return '';
            })(),
            // Scarti Previsti (Onda): preventivo fornitore, sempre visibile
            $fase->scarti_previsti ?? '',
            // Scarti Prinect (Macchina): consuntivo da Prinect API, solo stampa offset fase avviata
            (function() use ($fase) {
                $reparto = strtolower($fase->faseCatalogo->reparto->nome ?? '');
                if ($reparto === 'stampa offset' && (int)$fase->stato >= 2 && ($fase->fogli_scarto ?? 0) > 0) {
                    return $fase->fogli_scarto;
                }
                return '';
            })(),
            // Scarti Reali (Operatore): dichiarati a fine fase, solo se stato >= 2
            ((int)$fase->stato >= 2 && $fase->scarti !== null) ? $fase->scarti : '',
            // Cliché (auto/manual, sola lettura)
            $ordine && $ordine->cliche ? $ordine->cliche->label() : '',
            // Qta Prodotta Prinect (fogli_buoni, sola lettura)
            $fase->fogli_buoni ?? '',
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
            'Z' => 16,  // Ordine Cliente
            'AA' => 14, // N. DDT Vendita
            'AB' => 14, // Vettore DDT
            'AC' => 10, // Qta DDT
            'AD' => 30, // Note Fasi Successive
            'AE' => 18, // Colori
            'AF' => 14, // Fustella
            'AG' => 18, // Esterno
            'AH' => 10, // Ore Prev.
            'AI' => 10, // Ore Lav.
            'AJ' => 14, // Scarti Previsti (Onda)
            'AK' => 16, // Scarti Prinect (Macchina)
            'AL' => 16, // Scarti Reali (Operatore)
            'AM' => 12, // Cliché
            'AN' => 14, // Qta Prod. Prinect
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $nonEditabili = ['A', 'B', 'S', 'T', 'U', 'AE', 'AF', 'AG', 'AH', 'AI', 'AK', 'AL'];

        // Header: sfondo nero, testo bianco, grassetto
        $sheet->getStyle('A1:AL1')->applyFromArray([
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
        $sheet->setAutoFilter('A1:AL1');

        return [];
    }
}
