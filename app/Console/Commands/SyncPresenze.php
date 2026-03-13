<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncPresenze extends Command
{
    protected $signature = 'presenze:sync';
    protected $description = 'Sincronizza timbrature dal file NetTime';

    // Path UNC al file timbrature (copiato dal .253 ogni minuto)
    const TIMBRATURE_PATH = '\\\\192.168.1.253\\timbrature\\timbrature.txt';
    const PRESENZE_PATH = '\\\\192.168.1.253\\timbrature\\presenze.txt';

    public function handle()
    {
        // 1. Assicura che la tabella esista
        $this->ensureTable();

        // 2. Sync anagrafica (solo se vuota)
        $count = DB::table('nettime_anagrafica')->count();
        if ($count === 0) {
            $this->syncAnagrafica();
        }

        // 3. Sync timbrature di oggi
        $this->syncTimbrature();

        $this->info('Sync presenze completata.');
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
        // Il file è una riga continua con record separati dal pattern "011900RP" o "0119PRRP"
        // Ogni record inizia con "0119..." e contiene matricola (6 cifre), cognome (40 char), nome (34 char)
        preg_match_all('/011[09](?:00|PR)RP\s*(\d{6})([A-ZÀ-Ú\' ]{40})([A-ZÀ-Ú\' ]{1,40})/u', $content, $matches, PREG_SET_ORDER);

        $inseriti = 0;
        foreach ($matches as $m) {
            $matricola = $m[1];
            $cognome = trim($m[2]);
            $nome = trim($m[3]);

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
        $path = self::TIMBRATURE_PATH;
        if (!file_exists($path)) {
            $this->warn("File timbrature non trovato: $path");
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $oggi = Carbon::today()->format('dmy'); // es: 130326

        $nuove = 0;
        foreach ($lines as $line) {
            // Formato: R001001000 00000654 130326 1159 E
            // oppure:  001001000 00000654 030323 0756 E
            if (!preg_match('/^R?(\d{9})\s+(\d{8})\s+(\d{6})\s+(\d{4})\s+([EU])/', $line, $m)) {
                continue;
            }

            $terminale = $m[1];
            $matricola = ltrim($m[2], '0'); // Rimuovi zeri iniziali per matching
            $matricola = str_pad($matricola, 6, '0', STR_PAD_LEFT); // Pad a 6 come anagrafica
            $dataStr = $m[3]; // ddmmyy
            $oraStr = $m[4];  // HHmm
            $verso = $m[5];

            // Solo timbrature degli ultimi 7 giorni per non importare tutto lo storico
            $data = Carbon::createFromFormat('dmy', $dataStr);
            if ($data->lt(Carbon::today()->subDays(7))) continue;

            $dataOra = $data->format('Y-m-d') . ' ' . substr($oraStr, 0, 2) . ':' . substr($oraStr, 2, 2) . ':00';

            try {
                DB::table('nettime_timbrature')->insertOrIgnore([
                    'matricola' => $matricola,
                    'data_ora' => $dataOra,
                    'verso' => $verso,
                    'terminale' => $terminale,
                ]);
                $nuove++;
            } catch (\Exception $e) {
                // Duplicato, ignora
            }
        }

        $this->info("Timbrature: $nuove nuove importate.");
    }
}
