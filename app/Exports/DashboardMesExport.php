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
use App\Http\Services\PrinectService;
use App\Http\Services\PrinectSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

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
    private ?array $scartiOnda = null;
    private array $inkCache = [];

    public function __construct(string $operatore, int $stato, string $titolo)
    {
        $this->operatore = $operatore;
        $this->stato = $stato;
        $this->titolo = $titolo;
    }

    /**
     * Pre-fetch scarti da Onda (OC_TotScarti) mappati per commessa + codice macchina.
     * Mappa: ["commessa|CodMacchina" => SUM(OC_TotScarti)]
     * Inoltre mappa la fase MES (es. STAMPAXL106.1) alla macchina Onda (XL106-1) via PRDDocFasi.
     */
    private function caricaScartiOnda(): array
    {
        if ($this->scartiOnda !== null) return $this->scartiOnda;

        $map = [];
        try {
            $rows = DB::connection('onda')->select("
                SELECT
                    t.CodCommessa AS commessa,
                    f.CodFase AS cod_fase,
                    ext.OC_CodMacchina AS cod_macchina,
                    SUM(ext.OC_TotScarti) AS tot_scarti
                FROM ATTDocTeste t
                INNER JOIN PRDDocTeste p ON t.CodCommessa = p.CodCommessa
                INNER JOIN PRDDocFasi f ON p.IdDoc = f.IdDoc
                INNER JOIN OC_ATTDocRigheExt ext
                    ON ext.OC_IdDoc = t.IdDoc
                   AND ext.OC_CodMacchina = f.CodMacchina
                WHERE t.TipoDocumento = '2'
                  AND ext.OC_TotScarti > 0
                GROUP BY t.CodCommessa, f.CodFase, ext.OC_CodMacchina
            ");
            foreach ($rows as $r) {
                $commessa = trim($r->commessa);
                $faseCod = trim($r->cod_fase ?? '');
                // Chiave per fase Onda (STAMPAXL106.1, ecc.)
                $map[$commessa . '|' . $faseCod] = (int) $r->tot_scarti;
            }
        } catch (\Exception $e) {
            // Onda non raggiungibile — mappa vuota
        }

        $this->scartiOnda = $map;
        return $map;
    }

    /**
     * Inchiostro Prinect (g) totale CMYK per commessa.
     * Cache per-commessa (24h): non cambia dopo stampa terminata.
     * Se offline/errore: null.
     */
    private function inchiostroPrinect(?string $commessa): ?float
    {
        if (!$commessa) return null;
        $jobId = ltrim(explode('-', $commessa)[0] ?? '', '0');
        if (!$jobId || !is_numeric($jobId)) return null;
        if (isset($this->inkCache[$commessa])) return $this->inkCache[$commessa];

        return $this->inkCache[$commessa] = Cache::remember(
            "prinect_ink_total_{$commessa}",
            86400,
            function () use ($jobId) {
                try {
                    $service = app(PrinectService::class);
                    $wsData = $service->getJobWorksteps($jobId);
                    $worksteps = collect($wsData['worksteps'] ?? [])
                        ->filter(fn($ws) => in_array('ConventionalPrinting', $ws['types'] ?? []))
                        ->filter(fn($ws) => ($ws['status'] ?? '') === 'COMPLETED');
                    if ($worksteps->isEmpty()) return null;

                    $tot = 0;
                    foreach ($worksteps as $ws) {
                        $ink = $service->getWorkstepInkConsumption($jobId, $ws['id']);
                        foreach (($ink['inkConsumptions'] ?? []) as $c) {
                            $tot += (float) ($c['estimatedConsumption'] ?? 0);
                        }
                    }
                    // Prinect ritorna in kg → converto in grammi
                    return round($tot * 1000, 1);
                } catch (\Exception $e) {
                    return null;
                }
            }
        );
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
            'Scarti', 'Scarti Prinect', 'Cliché', 'Qta Prod. Prinect', 'Scarti Reali', 'Inchiostro Prinect (g)',
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
            // Scarti (editabile)
            $fase->scarti ?? '',
            // Scarti Previsti: per reparto "stampa offset" usa fogli_scarto da Prinect; altrimenti scarti_previsti da Onda
            (function() use ($fase) {
                $reparto = strtolower($fase->faseCatalogo->reparto->nome ?? '');
                if ($reparto === 'stampa offset' && ($fase->fogli_scarto ?? 0) > 0) {
                    return $fase->fogli_scarto;
                }
                return $fase->scarti_previsti ?? '';
            })(),
            // Cliché (auto/manual, sola lettura)
            $ordine && $ordine->cliche ? $ordine->cliche->label() : '',
            // Qta Prodotta Prinect (fogli_buoni, sola lettura)
            $fase->fogli_buoni ?? '',
            // Scarti Reali (OC_TotScarti da Onda preventivo articoli lavorazione, sola lettura)
            (function() use ($fase, $ordine) {
                $map = $this->caricaScartiOnda();
                $commessa = $ordine->commessa ?? '';
                $faseNome = $fase->fase ?? '';
                $faseBase = preg_replace('/\.\d+$/', '', $faseNome);
                $tentativi = [$faseNome, $faseBase];
                if (preg_match('/^STAMPA/i', $faseBase)) {
                    $tentativi[] = 'STAMPA';
                }
                foreach ($tentativi as $tentativo) {
                    if (!$tentativo) continue;
                    $chiave = $commessa . '|' . $tentativo;
                    if (isset($map[$chiave])) return $map[$chiave];
                }
                return '';
            })(),
            // Inchiostro Prinect (g): totale CMYK solo per fasi stampa offset (sola lettura)
            (function() use ($fase, $ordine) {
                $reparto = strtolower($fase->faseCatalogo->reparto->nome ?? '');
                if ($reparto !== 'stampa offset') return '';
                $val = $this->inchiostroPrinect($ordine->commessa ?? null);
                return $val !== null ? $val : '';
            })(),
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
            'AJ' => 10, // Scarti
            'AK' => 12, // Scarti Prev.
            'AL' => 12, // Cliché
            'AM' => 14, // Qta Prod. Prinect
            'AN' => 14, // Scarti Reali
            'AO' => 16, // Inchiostro Prinect (g)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();
        $nonEditabili = ['A', 'B', 'S', 'T', 'U', 'AE', 'AF', 'AG', 'AH', 'AI', 'AK', 'AL', 'AM', 'AN', 'AO'];

        // Header: sfondo nero, testo bianco, grassetto
        $sheet->getStyle('A1:AO1')->applyFromArray([
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
        $sheet->setAutoFilter('A1:AO1');

        return [];
    }
}
