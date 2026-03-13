<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncPresenze extends Command
{
    protected $signature = 'presenze:sync {--storico : Importa tutto lo storico (no filtro 7gg)}';
    protected $description = 'Sincronizza timbrature dal file NetTime';

    // Path primario: direttamente dal PC NetTime (.34)
    const TIMBRATURE_PATH_PRIMARY = '\\\\192.168.1.34\\NetTime\\TIMBRA\\TIMBRACP.BKP';
    // Fallback: copia su .253
    const TIMBRATURE_PATH_FALLBACK = '\\\\192.168.1.253\\timbrature\\timbrature.txt';
    const PRESENZE_PATH = '\\\\192.168.1.253\\timbrature\\presenze.txt';

    public function handle()
    {
        // 0. Assicura connessione alla share .34
        $this->ensureNetUse();

        // 1. Assicura che la tabella esista
        $this->ensureTable();

        // 2. Sync anagrafica (ogni esecuzione, updateOrInsert)
        $this->syncAnagrafica();

        // 3. Sync timbrature
        $this->syncTimbrature();

        $this->info('Sync presenze completata.');
    }

    protected function ensureNetUse()
    {
        // Se la share .34 non è raggiungibile, prova a connetterla
        if (!file_exists(self::TIMBRATURE_PATH_PRIMARY)) {
            @exec('net use \\\\192.168.1.34\\NetTime /user:mes M3s@Nappa26 2>&1', $out, $code);
            if ($code !== 0) {
                $this->warn("Share .34 non raggiungibile: " . implode(' ', $out));
            }
        }
    }

    protected function ensureTable()
    {
        // Tabella anagrafica
        if (!DB::getSchemaBuilder()->hasTable('nettime_anagrafica')) {
            DB::statement("CREATE TABLE nettime_anagrafica (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                matricola VARCHAR(10) NOT NULL UNIQUE,
                cognome VARCHAR(50),
                nome VARCHAR(50)
            )");
            $this->info('Tabella nettime_anagrafica creata.');
        }

        // Tabella timbrature
        if (!DB::getSchemaBuilder()->hasTable('nettime_timbrature')) {
            DB::statement("CREATE TABLE nettime_timbrature (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                matricola VARCHAR(10) NOT NULL,
                data_ora DATETIME NOT NULL,
                verso CHAR(1) NOT NULL COMMENT 'E=entrata, U=uscita',
                terminale VARCHAR(20),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_timbr (matricola, data_ora, verso),
                INDEX idx_data (data_ora),
                INDEX idx_matricola (matricola)
            )");
            $this->info('Tabella nettime_timbrature creata.');
        }
    }

    protected function syncAnagrafica()
    {
        $path = self::PRESENZE_PATH;
        if (!file_exists($path)) {
            $this->warn("File anagrafica non trovato: $path");
            return;
        }

        $content = file_get_contents($path);

        // Cattura header + matricola + blocco 80 char (cognome + nome padding)
        // Poi split intelligente: cognome e nome separati da 2+ spazi
        preg_match_all('/011[09](?:00|PR)RP\s*(\d{6})([A-Za-zÀ-ú\' ]{20,80})/u', $content, $matches, PREG_SET_ORDER);

        $inseriti = 0;
        foreach ($matches as $m) {
            $matricola = $m[1];
            $blocco = $m[2];

            // Rimuovi tutto dopo il primo digit (dati successivi nel record)
            $blocco = preg_replace('/\d.*$/', '', $blocco);

            // Split cognome/nome: separati da 2+ spazi consecutivi
            $parts = preg_split('/\s{2,}/', trim($blocco));

            $cognome = trim($parts[0] ?? '');
            $nome = trim($parts[1] ?? '');

            if (empty($cognome)) continue;

            DB::table('nettime_anagrafica')->updateOrInsert(
                ['matricola' => $matricola],
                ['cognome' => $cognome, 'nome' => $nome]
            );
            $inseriti++;
        }

        $this->info("Anagrafica: $inseriti dipendenti importati.");
    }

    protected function syncTimbrature()
    {
        // Prova prima .34 (fonte diretta), poi fallback .253
        $path = null;
        if (file_exists(self::TIMBRATURE_PATH_PRIMARY)) {
            $path = self::TIMBRATURE_PATH_PRIMARY;
            $this->info("Lettura da .34 (NetTime diretto)");
        } elseif (file_exists(self::TIMBRATURE_PATH_FALLBACK)) {
            $path = self::TIMBRATURE_PATH_FALLBACK;
            $this->info("Lettura da .253 (fallback)");
        } else {
            $this->warn("Nessun file timbrature trovato (.34 e .253 non raggiungibili)");
            return;
        }

        $storico = $this->option('storico');
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->info("Righe nel file: " . count($lines));

        $nuove = 0;
        $saltate = 0;
        $batch = [];

        foreach ($lines as $line) {
            // Formato: R001001000 00000654 130326 1159 E
            if (!preg_match('/^R?(\d{9})\s+(\d{8})\s+(\d{6})\s+(\d{4})\s+([EU])/', $line, $m)) {
                continue;
            }

            $terminale = $m[1];
            $matricola = ltrim($m[2], '0');
            $matricola = str_pad($matricola, 6, '0', STR_PAD_LEFT);
            $dataStr = $m[3]; // ddmmyy
            $oraStr = $m[4];  // HHmm
            $verso = $m[5];

            // Filtro temporale: solo ultimi 7gg (a meno che --storico)
            if (!$storico) {
                $data = Carbon::createFromFormat('dmy', $dataStr);
                if ($data->lt(Carbon::today()->subDays(7))) {
                    $saltate++;
                    continue;
                }
            } else {
                $data = Carbon::createFromFormat('dmy', $dataStr);
            }

            $dataOra = $data->format('Y-m-d') . ' ' . substr($oraStr, 0, 2) . ':' . substr($oraStr, 2, 2) . ':00';

            $batch[] = [
                'matricola' => $matricola,
                'data_ora' => $dataOra,
                'verso' => $verso,
                'terminale' => $terminale,
            ];

            // Insert in batch da 500
            if (count($batch) >= 500) {
                $nuove += $this->insertBatch($batch);
                $batch = [];
            }
        }

        // Inserisci il resto
        if (!empty($batch)) {
            $nuove += $this->insertBatch($batch);
        }

        $this->info("Timbrature: $nuove nuove importate" . ($saltate ? ", $saltate saltate (>7gg)" : "") . ".");
    }

    protected function insertBatch(array $batch): int
    {
        try {
            return DB::table('nettime_timbrature')->insertOrIgnore($batch);
        } catch (\Exception $e) {
            // Fallback: inserisci una per una se il batch fallisce
            $n = 0;
            foreach ($batch as $row) {
                try {
                    DB::table('nettime_timbrature')->insertOrIgnore([$row]);
                    $n++;
                } catch (\Exception $e2) {}
            }
            return $n;
        }
    }
}
