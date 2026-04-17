<?php

namespace App\Console\Commands;

use App\Models\ClicheAnagrafica;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ClicheImport extends Command
{
    protected $signature = 'cliche:import {path? : Path al file Excel}';
    protected $description = 'Importa anagrafica cliché da file Excel (colonne: A=numero, B=descrizione, C=qta, D=note, E=scatola)';

    public function handle(): int
    {
        $path = $this->argument('path') ?: storage_path('app/cliche/cliche.xlsx');
        if (!file_exists($path)) {
            $this->error("File non trovato: $path");
            return 1;
        }

        error_reporting(E_ALL & ~E_DEPRECATED);

        $this->info("Lettura: $path");
        $ss = IOFactory::load($path);
        $rows = $ss->getActiveSheet()->toArray(null, false, false, true);

        $imported = 0; $updated = 0; $skipped = 0;
        $first = true;
        foreach ($rows as $r) {
            if ($first) { $first = false; continue; }
            $numero = trim((string)($r['A'] ?? ''));
            $desc = trim((string)($r['B'] ?? ''));
            if ($numero === '' || $desc === '' || !is_numeric($numero)) { $skipped++; continue; }
            $qta = is_numeric($r['C'] ?? null) ? (int) $r['C'] : null;
            $note = trim((string)($r['D'] ?? '')) ?: null;
            $scatola = is_numeric($r['E'] ?? null) ? (int) $r['E'] : null;

            $existing = ClicheAnagrafica::where('numero', (int) $numero)->first();
            if ($existing) {
                $existing->descrizione_raw = $desc;
                $existing->qta = $qta;
                $existing->note = $note;
                $existing->scatola = $scatola;
                if ($existing->isDirty()) { $existing->save(); $updated++; }
            } else {
                ClicheAnagrafica::create([
                    'numero' => (int) $numero,
                    'descrizione_raw' => $desc,
                    'qta' => $qta,
                    'note' => $note,
                    'scatola' => $scatola,
                ]);
                $imported++;
            }
        }
        $this->info("Importati: $imported | aggiornati: $updated | skip: $skipped");
        return 0;
    }
}
