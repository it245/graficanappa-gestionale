<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Importa TUTTI i dati da GN_Materie_Prime.xlsx + GN_Database_Costi_Completo.xlsx in DB.
 * Popola:
 *   - costi_raw_excel: dump completo di ogni sheet (backup integrale)
 *   - macchine_costi, costi_avviamento, costi_fasce_tiratura, costi_aggiuntivi: dati strutturati
 *     per le 12 macchine (XL106, Konica14000, Bobst Novacut, Visionfold, Brausse, ecc)
 *   - materie_prime_carte: cartoncini Cartularia + patinate + uso mano
 */
class ImportCostiExcel extends Command
{
    protected $signature = 'costi:import {--path=C:/Users/Giovanni/Downloads}';
    protected $description = 'Importa GN_Materie_Prime.xlsx + GN_Database_Costi_Completo.xlsx in DB';

    public function handle(): int
    {
        $path = rtrim($this->option('path'), '/\\');
        $files = [
            $path . '/GN_Materie_Prime.xlsx',
            $path . '/GN_Database_Costi_Completo.xlsx',
        ];

        foreach ($files as $f) {
            if (!is_file($f)) {
                $this->error("File non trovato: {$f}");
                return 1;
            }
        }

        // 1) DUMP RAW di ogni sheet
        $this->info("=== DUMP RAW EXCEL ===");
        DB::table('costi_raw_excel')->delete();
        $totRow = 0;
        foreach ($files as $f) {
            $reader = IOFactory::createReaderForFile($f);
            $reader->setReadDataOnly(true);
            $ss = $reader->load($f);
            $fname = basename($f);
            foreach ($ss->getSheetNames() as $sheetName) {
                $sheet = $ss->getSheetByName($sheetName);
                $rows = $sheet->toArray(null, true, true, false);
                foreach ($rows as $i => $row) {
                    $empty = true;
                    foreach ($row as $c) if ($c !== null && $c !== '') { $empty = false; break; }
                    if ($empty) continue;
                    DB::table('costi_raw_excel')->insert([
                        'file' => $fname,
                        'sheet' => $sheetName,
                        'riga' => $i + 1,
                        'dati' => json_encode(array_values($row), JSON_UNESCAPED_UNICODE),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $totRow++;
                }
                $this->line("  RAW: {$fname} → {$sheetName}");
            }
        }
        $this->info("Totale righe raw: {$totRow}");

        // 2) SEED STRUTTURATO macchine + listini + carte
        $this->info("\n=== SEED STRUTTURATO ===");
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\CostiMacchineSeeder', '--force' => true]);
        $this->call('db:seed', ['--class' => 'Database\\Seeders\\MateriePrimeCarteSeeder', '--force' => true]);

        $stats = [
            'macchine_costi'      => DB::table('macchine_costi')->count(),
            'costi_avviamento'    => DB::table('costi_avviamento')->count(),
            'costi_fasce_tiratura'=> DB::table('costi_fasce_tiratura')->count(),
            'costi_aggiuntivi'    => DB::table('costi_aggiuntivi')->count(),
            'materie_prime_carte' => DB::table('materie_prime_carte')->count(),
            'costi_raw_excel'     => DB::table('costi_raw_excel')->count(),
        ];
        $this->info("\n=== STATS ===");
        foreach ($stats as $t => $c) $this->line("  {$t}: {$c} righe");

        return 0;
    }
}
