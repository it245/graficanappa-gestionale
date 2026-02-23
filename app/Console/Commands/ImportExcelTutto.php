<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Models\FasiCatalogo;
use App\Models\Reparto;
use App\Services\OndaSyncService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;

class ImportExcelTutto extends Command
{
    protected $signature = 'import:excel-tutto {file : Percorso del file Excel} {--dry-run : Mostra cosa verrebbe importato senza salvare}';
    protected $description = 'Importa ordini e fasi dal foglio "tutto" del file Excel del capo';

    private $mappaReparti;

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

        $this->info("Caricamento foglio 'tutto' da: $file");

        $previousReporting = error_reporting(E_ALL & ~E_DEPRECATED);

        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly(['tutto']);
            $spreadsheet = $reader->load($file);
        } catch (\Exception $e) {
            $this->error('Errore apertura file: ' . $e->getMessage());
            return 1;
        } finally {
            error_reporting($previousReporting);
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, false, false, true);

        // Carica mappa reparti da OndaSyncService (reflection)
        $this->mappaReparti = $this->getMappaReparti();

        $isFirst = true;
        $ordiniCreati = 0;
        $ordiniAggiornati = 0;
        $fasiCreate = 0;
        $fasiSkippate = 0;
        $errori = 0;

        // Raggruppa per commessa+codart+descrizione (stessa logica di OndaSync)
        $righe = [];
        foreach ($rows as $rowNum => $row) {
            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            $commessa = trim((string) ($row['D'] ?? ''));
            if (!$commessa) continue;

            $righe[] = [
                'row_num' => $rowNum,
                'commessa' => $commessa,
                'cliente' => trim((string) ($row['E'] ?? '')),
                'stato' => $row['F'] ?? 0,
                'cod_art' => trim((string) ($row['G'] ?? '')),
                'descrizione' => trim((string) ($row['H'] ?? '')),
                'qta_richiesta' => $this->parseNumeric($row['I'] ?? null),
                'fase' => trim((string) ($row['J'] ?? '')),
                'um' => trim((string) ($row['K'] ?? '')) ?: 'FG',
                'qta_prod' => $this->parseNumeric($row['L'] ?? null),
                'data_consegna' => $this->parseExcelDate($row['M'] ?? null),
                'cod_carta' => trim((string) ($row['O'] ?? '')),
                'carta' => trim((string) ($row['P'] ?? '')),
                'qta_carta' => $this->parseNumeric($row['Q'] ?? null),
                'note' => trim((string) ($row['R'] ?? '')),
                'data_registrazione' => $this->parseExcelDate($row['C'] ?? null),
            ];
        }

        $this->info("Righe lette dal foglio: " . count($righe));

        // Raggruppa per commessa + cod_art + descrizione
        $gruppi = collect($righe)->groupBy(function ($r) {
            return $r['commessa'] . '|' . $r['cod_art'] . '|' . $r['descrizione'];
        });

        $this->info("Commesse uniche (commessa+codart+desc): " . $gruppi->count());

        $bar = $this->output->createProgressBar($gruppi->count());
        $bar->start();

        foreach ($gruppi as $chiave => $righeGruppo) {
            $prima = $righeGruppo->first();

            // Cerca ordine esistente
            $ordine = Ordine::where('commessa', $prima['commessa'])
                ->where('cod_art', $prima['cod_art'])
                ->first();

            $datiOrdine = [
                'cliente_nome' => $prima['cliente'],
                'descrizione' => $prima['descrizione'],
                'qta_richiesta' => $prima['qta_richiesta'],
                'data_prevista_consegna' => $prima['data_consegna'],
                'cod_carta' => $prima['cod_carta'],
                'carta' => $prima['carta'],
                'qta_carta' => $prima['qta_carta'],
            ];

            if (!$dryRun) {
                if ($ordine) {
                    $ordine->update($datiOrdine);
                    $ordiniAggiornati++;
                } else {
                    $ordine = Ordine::create(array_merge([
                        'commessa' => $prima['commessa'],
                        'cod_art' => $prima['cod_art'],
                        'stato' => 0,
                        'data_registrazione' => $prima['data_registrazione'] ?? now()->toDateString(),
                        'um' => $prima['um'],
                    ], $datiOrdine));
                    $ordiniCreati++;
                }
            } else {
                if ($ordine) {
                    $ordiniAggiornati++;
                } else {
                    $ordiniCreati++;
                }
            }

            // Fasi del gruppo
            foreach ($righeGruppo as $riga) {
                $faseNome = $riga['fase'];
                if (!$faseNome) {
                    $fasiSkippate++;
                    continue;
                }

                // Controlla se la fase esiste già per questo ordine
                if ($ordine) {
                    $faseEsistente = OrdineFase::where('ordine_id', $ordine->id)
                        ->where('fase', $faseNome)
                        ->first();

                    if ($faseEsistente) {
                        $fasiSkippate++;
                        continue;
                    }
                }

                // Risolvi reparto dalla mappa
                $repartoNome = $this->mappaReparti[$faseNome] ?? null;
                $faseCatalogoId = null;

                if ($repartoNome && !$dryRun) {
                    $reparto = Reparto::where('nome', $repartoNome)->first();
                    if ($reparto) {
                        $faseCatalogo = FasiCatalogo::firstOrCreate(
                            ['nome' => $faseNome],
                            ['reparto_id' => $reparto->id]
                        );
                        $faseCatalogoId = $faseCatalogo->id;
                    }
                }

                $statoFase = is_numeric($riga['stato']) ? (int) $riga['stato'] : 0;
                // Non importare fasi già terminate
                if ($statoFase >= 3) {
                    $fasiSkippate++;
                    continue;
                }

                if (!$dryRun) {
                    OrdineFase::create([
                        'ordine_id' => $ordine->id,
                        'fase' => $faseNome,
                        'fase_catalogo_id' => $faseCatalogoId,
                        'stato' => $statoFase,
                        'qta_prod' => $riga['qta_prod'],
                        'um' => $riga['um'],
                        'note' => $riga['note'],
                    ]);
                }

                $fasiCreate++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("=== Riepilogo ===");
        $this->info("Ordini creati:     $ordiniCreati");
        $this->info("Ordini aggiornati: $ordiniAggiornati");
        $this->info("Fasi create:       $fasiCreate");
        $this->info("Fasi skippate:     $fasiSkippate (già esistenti o senza nome)");

        if ($dryRun) {
            $this->warn('Nessuna modifica salvata (dry-run).');
        } else {
            $this->info('Import completato.');
        }

        // Pulizia file temporaneo
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return 0;
    }

    private function getMappaReparti(): array
    {
        try {
            $reflection = new \ReflectionMethod(OndaSyncService::class, 'getMappaReparti');
            $reflection->setAccessible(true);
            return $reflection->invoke(null);
        } catch (\Exception $e) {
            $this->warn('Impossibile caricare mappa reparti da OndaSyncService, uso mappa vuota.');
            return [];
        }
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
