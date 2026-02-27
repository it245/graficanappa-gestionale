<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use App\Models\Reparto;
use App\Services\OndaSyncService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

class ImportExcelTutto extends Command
{
    protected $signature = 'import:excel-tutto
        {file : Percorso del file Excel}
        {--pulisci : Svuota ordini e fasi prima dell\'import}
        {--dry-run : Mostra cosa verrebbe importato senza salvare}';

    protected $description = 'Importa ordini e fasi dal foglio "tutto" del file Excel MES';

    // Mappatura stati Excel → MES: 0→0, 1→2, 2→3, 3→4
    private const MAPPA_STATI = [
        0 => 0,  // caricato
        1 => 2,  // avviato (Excel "in lavorazione" → MES "avviato")
        2 => 3,  // terminato
        3 => 4,  // consegnato/spedito
    ];

    // Fasi non presenti in OndaSyncService::getMappaReparti()
    private const FASI_EXTRA = [
        'esterno'                  => 'esterno',
        'ALL.COFANETTO.LEGOKART'   => 'esterno',
        'BROSSPUR'                 => 'legatoria',
        'CLICHESTAMPACALDO1'       => 'stampa a caldo',
        'BROSSFRESATA/A4EST'       => 'esterno',
        'FASCETTATURA'             => 'legatoria',
        'ACCOPP.FUST.INCOLL.FOGLI' => 'esterno',
        'BRT'                      => 'spedizione',
    ];

    /**
     * Colonne del foglio "tutto":
     *  A=dataregistrazione, B=commessa, C=cliente, D=stato,
     *  E=codart, F=descrizione, G=qta richiesta, H=fase, I=um carta,
     *  J=qtaprodotta, K=datapresconsegna, L=pronto per consegna,
     *  M=codcarta, N=carta, O=qtacarta, P=note, Q=ore, R=time out
     */
    public function handle()
    {
        $file = $this->argument('file');
        if (!file_exists($file)) {
            $this->error("File non trovato: $file");
            return 1;
        }

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn('=== MODALITA\' DRY-RUN: nessuna modifica al database ===');
        }

        // --- PULIZIA ---
        if ($this->option('pulisci') && !$dryRun) {
            if ($this->confirm('Svuotare TUTTI gli ordini e le fasi dal database?', true)) {
                OrdineFase::query()->delete();
                Ordine::query()->delete();
                $this->info('Database svuotato.');
            }
        }

        // --- CARICAMENTO EXCEL ---
        $this->info("Caricamento foglio 'tutto' da: $file");
        $previousReporting = error_reporting(E_ALL & ~E_DEPRECATED);

        // ReadFilter: solo colonne A-R per risparmiare memoria
        $colFilter = new class implements IReadFilter {
            public function readCell($columnAddress, $row, $worksheetName = '') {
                return strlen($columnAddress) === 1 && $columnAddress <= 'R';
            }
        };

        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly(['tutto']);
            $reader->setReadFilter($colFilter);
            $spreadsheet = $reader->load($file);
        } catch (\Exception $e) {
            $this->error('Errore apertura file: ' . $e->getMessage());
            return 1;
        } finally {
            error_reporting($previousReporting);
        }

        $sheet = $spreadsheet->getActiveSheet();
        $highRow = $sheet->getHighestRow();
        $this->info("Righe totali nel foglio: " . ($highRow - 1));

        // --- MAPPA REPARTI ---
        $mappaReparti = $this->getMappaReparti();

        // --- PARSING RIGHE (riga per riga per risparmiare memoria) ---
        $righe = [];
        $skippedNoStato = 0;

        for ($rowNum = 2; $rowNum <= $highRow; $rowNum++) {
            $commessa = trim((string) ($sheet->getCell("B{$rowNum}")->getValue() ?? ''));
            if (!$commessa) continue;

            $statoRaw = $sheet->getCell("D{$rowNum}")->getValue();
            if ($statoRaw === null || $statoRaw === '') {
                $skippedNoStato++;
                continue;
            }

            $statoExcel = (int) $statoRaw;
            if (!isset(self::MAPPA_STATI[$statoExcel])) {
                $skippedNoStato++;
                continue;
            }

            $righe[] = [
                'commessa'         => $commessa,
                'cliente'          => trim((string) ($sheet->getCell("C{$rowNum}")->getValue() ?? '')),
                'stato_excel'      => $statoExcel,
                'stato_mes'        => self::MAPPA_STATI[$statoExcel],
                'cod_art'          => trim((string) ($sheet->getCell("E{$rowNum}")->getValue() ?? '')),
                'descrizione'      => trim((string) ($sheet->getCell("F{$rowNum}")->getValue() ?? '')),
                'qta_richiesta'    => $this->parseNumeric($sheet->getCell("G{$rowNum}")->getValue()),
                'fase'             => trim((string) ($sheet->getCell("H{$rowNum}")->getValue() ?? '')),
                'um'               => trim((string) ($sheet->getCell("I{$rowNum}")->getValue() ?? '')) ?: 'FG',
                'qta_prod'         => $this->parseNumeric($sheet->getCell("J{$rowNum}")->getValue()),
                'data_consegna'    => $this->parseExcelDate($sheet->getCell("K{$rowNum}")->getValue()),
                'cod_carta'        => trim((string) ($sheet->getCell("M{$rowNum}")->getValue() ?? '')),
                'carta'            => trim((string) ($sheet->getCell("N{$rowNum}")->getValue() ?? '')),
                'qta_carta'        => $this->parseNumeric($sheet->getCell("O{$rowNum}")->getValue()),
                'note'             => trim((string) ($sheet->getCell("P{$rowNum}")->getValue() ?? '')),
                'ore'              => $this->parseNumeric($sheet->getCell("Q{$rowNum}")->getValue()),
                'data_registrazione' => $this->parseExcelDate($sheet->getCell("A{$rowNum}")->getValue()),
            ];
        }

        // Libera memoria del foglio
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $sheet);

        $this->info("Righe con stato 0-3: " . count($righe) . " (scartate senza stato: $skippedNoStato)");

        // --- RAGGRUPPA PER COMMESSA + COD_ART ---
        $gruppi = collect($righe)->groupBy(fn($r) => $r['commessa'] . '|' . $r['cod_art']);
        $this->info("Ordini unici (commessa+codart): " . $gruppi->count());

        // --- IMPORT ---
        $ordiniCreati = 0;
        $ordiniAggiornati = 0;
        $fasiCreate = 0;
        $fasiSkippate = 0;

        $bar = $this->output->createProgressBar($gruppi->count());
        $bar->start();

        foreach ($gruppi as $chiave => $righeGruppo) {
            $prima = $righeGruppo->first();

            $datiOrdine = [
                'cliente_nome'           => $prima['cliente'],
                'descrizione'            => $prima['descrizione'],
                'qta_richiesta'          => $prima['qta_richiesta'],
                'data_prevista_consegna' => $prima['data_consegna'],
                'cod_carta'              => $prima['cod_carta'],
                'carta'                  => $prima['carta'],
                'qta_carta'              => $prima['qta_carta'],
            ];

            if (!$dryRun) {
                $ordine = Ordine::where('commessa', $prima['commessa'])
                    ->where('cod_art', $prima['cod_art'])
                    ->first();

                if ($ordine) {
                    $ordine->update($datiOrdine);
                    $ordiniAggiornati++;
                } else {
                    $ordine = Ordine::create(array_merge([
                        'commessa'           => $prima['commessa'],
                        'cod_art'            => $prima['cod_art'],
                        'stato'              => 0,
                        'um'                 => $prima['um'],
                        'data_registrazione' => $prima['data_registrazione'] ?? now()->toDateString(),
                    ], $datiOrdine));
                    $ordiniCreati++;
                }
            } else {
                $ordiniCreati++;
            }

            // --- FASI ---
            foreach ($righeGruppo as $riga) {
                $faseNome = $riga['fase'];
                if (!$faseNome) {
                    $fasiSkippate++;
                    continue;
                }

                $faseCatalogoId = null;
                if (!$dryRun) {
                    $faseCatalogo = FasiCatalogo::where('nome', $faseNome)->first();
                    if (!$faseCatalogo) {
                        $repartoNome = $mappaReparti[$faseNome] ?? null;
                        if ($repartoNome) {
                            $reparto = Reparto::where('nome', $repartoNome)->first();
                            if ($reparto) {
                                $faseCatalogo = FasiCatalogo::create([
                                    'nome' => $faseNome,
                                    'reparto_id' => $reparto->id,
                                ]);
                                $this->newLine();
                                $this->warn("  Creata fase catalogo: {$faseNome} → {$repartoNome}");
                            }
                        }
                        if (!$faseCatalogo) {
                            $this->newLine();
                            $this->error("  Fase sconosciuta (no reparto): {$faseNome}");
                        }
                    }
                    $faseCatalogoId = $faseCatalogo?->id;

                    OrdineFase::create([
                        'ordine_id'       => $ordine->id,
                        'fase'            => $faseNome,
                        'fase_catalogo_id' => $faseCatalogoId,
                        'stato'           => $riga['stato_mes'],
                        'qta_prod'        => $riga['qta_prod'],
                        'qta_fase'        => $riga['qta_richiesta'],
                        'um'              => $riga['um'],
                        'note'            => $riga['note'],
                        'ore'             => $riga['ore'],
                        'data_fine'       => $riga['stato_mes'] >= 3 ? now() : null,
                    ]);
                }
                $fasiCreate++;
            }

            // Assicura che esista sempre la fase BRT1 (spedizione)
            if (!$dryRun && $ordine) {
                $hasBrt = OrdineFase::where('ordine_id', $ordine->id)
                    ->where(function ($q) {
                        $q->where('fase', 'BRT1')->orWhere('fase', 'brt1')->orWhere('fase', 'BRT');
                    })->exists();

                if (!$hasBrt) {
                    $repartoBrt = Reparto::firstOrCreate(['nome' => 'spedizione']);
                    $faseCatalogoBrt = FasiCatalogo::firstOrCreate(
                        ['nome' => 'BRT1'],
                        ['reparto_id' => $repartoBrt->id]
                    );
                    OrdineFase::create([
                        'ordine_id'        => $ordine->id,
                        'fase'             => 'BRT1',
                        'fase_catalogo_id' => $faseCatalogoBrt->id,
                        'qta_fase'         => $ordine->qta_richiesta ?? 0,
                        'um'               => 'FG',
                        'priorita'         => 96,
                        'stato'            => 0,
                    ]);
                    $fasiCreate++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("=== Riepilogo ===");
        $this->info("Ordini creati:     $ordiniCreati");
        $this->info("Ordini aggiornati: $ordiniAggiornati");
        $this->info("Fasi create:       $fasiCreate");
        $this->info("Fasi skippate:     $fasiSkippate");

        if ($dryRun) {
            $this->warn('Nessuna modifica salvata (dry-run).');
        } else {
            $this->info('Import completato.');
        }

        return 0;
    }

    private function getMappaReparti(): array
    {
        try {
            $reflection = new \ReflectionMethod(OndaSyncService::class, 'getMappaReparti');
            $reflection->setAccessible(true);
            $mappa = $reflection->invoke(null);
        } catch (\Exception $e) {
            $this->warn('Impossibile caricare mappa reparti da OndaSyncService, uso mappa base.');
            $mappa = [];
        }

        // Aggiungi fasi extra non presenti nel sync
        return array_merge($mappa, self::FASI_EXTRA);
    }

    private function parseNumeric($value): float
    {
        if ($value === null || $value === '' || $value === '-') return 0;
        return (float) str_replace(',', '.', (string) $value);
    }

    private function parseExcelDate($value): ?string
    {
        if ($value === null || $value === '' || $value === '-') return null;

        if (is_numeric($value) && (float) $value > 25000) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Exception $e) {}
        }

        $str = trim((string) $value);
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $str, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }
        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $str)) {
            return $str;
        }

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
