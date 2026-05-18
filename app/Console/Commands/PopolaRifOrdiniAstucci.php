<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ordine;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Popola Ordine.ordine_cliente leggendo ORDINE ASTUCCI.xlsx
 * (riferimento ordine Maxtris es. P01267).
 *
 * Match: commessa numerica (senza zeri/anno) + descrizione normalizzata
 *        (stesso algoritmo di DdtPdfService::normalizza).
 *
 * Excel atteso in: env('EXCEL_SYNC_PATH')/ORDINE ASTUCCI.xlsx
 *  - Col B: descrizione articolo
 *  - Col F: commessa (es. "67201" o "67201-67203" multi)
 *  - Col G: RIF ORD MAXTRIS (es. "P01267")
 */
class PopolaRifOrdiniAstucci extends Command
{
    protected $signature = 'ordini:popola-rif {--dry-run : Mostra modifiche senza salvare}';
    protected $description = 'Popola ordine_cliente sugli ordini MES leggendo ORDINE ASTUCCI.xlsx';

    public function handle(): int
    {
        $excelPath = env('EXCEL_SYNC_PATH', storage_path('app/excel_sync'));
        $file = rtrim($excelPath, '/\\') . DIRECTORY_SEPARATOR . 'ORDINE ASTUCCI.xlsx';

        if (!file_exists($file)) {
            $this->error("File non trovato: $file");
            return 1;
        }

        $this->info("Caricamento $file ...");
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();

        $mapDettaglio = [];   // 'commessa|descNormalizzata' => rif
        $mapCommessa = [];    // 'commessa' => primo rif trovato (fallback)
        $righeLette = 0;

        foreach ($sheet->getRowIterator(2) as $row) {
            $r = $row->getRowIndex();
            $commessa = trim((string) ($sheet->getCell("F$r")->getValue() ?? ''));
            $descrizione = trim((string) ($sheet->getCell("B$r")->getValue() ?? ''));
            $rif = trim((string) ($sheet->getCell("G$r")->getValue() ?? ''));

            if (!$rif) continue;
            $righeLette++;

            $commesse = array_map('trim', explode('-', $commessa));
            $tutteNum = collect($commesse)->every(fn($c) => is_numeric($c));

            if ($tutteNum && count($commesse) > 1) {
                foreach ($commesse as $c) {
                    $key = $c . '|' . self::normalizza($descrizione);
                    $mapDettaglio[$key] = $rif;
                    if (!isset($mapCommessa[$c])) $mapCommessa[$c] = $rif;
                }
            } else {
                $key = $commessa . '|' . self::normalizza($descrizione);
                $mapDettaglio[$key] = $rif;
                if (!isset($mapCommessa[$commessa])) $mapCommessa[$commessa] = $rif;
            }
        }

        $this->info("Righe lette: $righeLette | RIF unici detail: " . count($mapDettaglio) . " | per commessa: " . count($mapCommessa));

        // Itera ordini senza ordine_cliente popolato
        $ordini = Ordine::query()
            ->where(function ($q) {
                $q->whereNull('ordine_cliente')->orWhere('ordine_cliente', '');
            })
            ->get(['id', 'commessa', 'descrizione', 'cliente_nome']);

        $aggiornati = 0;
        $miss = 0;
        $dry = $this->option('dry-run');

        foreach ($ordini as $o) {
            $commCorta = ltrim(explode('-', $o->commessa)[0] ?? '', '0') ?: '0';
            $descNorm = self::normalizza($o->descrizione ?? '');

            $rif = $mapDettaglio[$commCorta . '|' . $descNorm] ?? null;
            if (!$rif) {
                $rif = $mapCommessa[$commCorta] ?? null;
            }

            if (!$rif) { $miss++; continue; }

            if (!$dry) {
                $o->ordine_cliente = $rif;
                $o->save();
            }
            $aggiornati++;
            if ($aggiornati <= 10) {
                $this->line("  ✓ ordine_id={$o->id} commessa={$o->commessa} → $rif");
            }
        }

        $this->info("\nRisultato:");
        $this->info("  Aggiornati: $aggiornati");
        $this->info("  Senza match: $miss");
        if ($dry) $this->warn("  DRY-RUN: nessuna modifica salvata. Rilancia senza --dry-run.");

        return 0;
    }

    /**
     * Stessa normalizzazione di DdtPdfService::normalizza (coerenza match).
     */
    private static function normalizza(string $desc): string
    {
        $desc = mb_strtoupper($desc);
        $stopwords = [
            'ASTUCCIO', 'ASTUCCI', 'AST.', 'AST',
            'VASSOIO', 'VASSOI', 'VASS.', 'VASS',
            'BOX', 'PACK', 'SCATOLA', 'CONFEZIONE',
            'FORMATO', 'SLEEVE', 'KIT', 'SET',
            'CARTONATO', 'COFANETTO',
        ];
        $desc = preg_replace('/\bCADEAUX?\b/', 'CADEAU', $desc);
        foreach ($stopwords as $w) {
            $desc = preg_replace('/\b' . preg_quote($w, '/') . '\b/u', '', $desc);
        }
        return preg_replace('/[^A-Z0-9]/', '', $desc);
    }
}
