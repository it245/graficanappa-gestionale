<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EanProdotto;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportEanProdotti extends Command
{
    protected $signature = 'import:ean-prodotti {file : Percorso del file Excel}';

    protected $description = 'Importa codici EAN prodotti da file Excel (colonna A=articolo, B=codice_ean)';

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File non trovato: {$file}");
            return 1;
        }

        $this->info("Lettura file: {$file}");

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Determina se la prima riga è un'intestazione
        $startRow = 0;
        if (!empty($rows[0]) && !is_numeric(str_replace([' ', '-'], '', $rows[0][1] ?? ''))) {
            $startRow = 1;
            $this->info("Prima riga considerata come intestazione, salto.");
        }

        $count = 0;
        $data = [];

        for ($i = $startRow; $i < count($rows); $i++) {
            $articolo = trim($rows[$i][0] ?? '');
            $codiceEan = trim($rows[$i][1] ?? '');

            if (empty($articolo) && empty($codiceEan)) {
                continue;
            }

            $data[] = [
                'articolo'   => $articolo,
                'codice_ean' => $codiceEan,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $count++;
        }

        // Truncate e reimport
        EanProdotto::truncate();
        $this->info("Tabella ean_prodotti svuotata.");

        // Insert a blocchi da 500
        foreach (array_chunk($data, 500) as $chunk) {
            EanProdotto::insert($chunk);
        }

        $this->info("Importati {$count} prodotti EAN.");
        return 0;
    }
}
