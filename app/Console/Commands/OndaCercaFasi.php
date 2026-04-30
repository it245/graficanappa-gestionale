<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Cerca fasi/lavorazioni in Onda DB matching keyword.
 * Esempio: php artisan onda:cerca-fasi "preparazione|taglio|fustella"
 */
class OndaCercaFasi extends Command
{
    protected $signature = 'onda:cerca-fasi {keywords : pattern separati da | (es. "preparazione|taglio|fustella")} {--limit=200}';
    protected $description = 'Cerca fasi/lavorazioni Onda matchando keywords nei nomi';

    public function handle(): int
    {
        $kws = explode('|', $this->argument('keywords'));
        $limit = (int) $this->option('limit');

        // Step 1: trova tabelle/colonne candidate (descrizione/nome/codice fasi)
        $this->info('=== Step 1: tabelle con "Fas" o "Lavoraz" nel nome ===');
        $tables = DB::connection('onda')->select("
            SELECT TABLE_SCHEMA, TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_TYPE = 'BASE TABLE'
              AND (TABLE_NAME LIKE '%Fas%' OR TABLE_NAME LIKE '%Lavoraz%' OR TABLE_NAME LIKE '%Reparto%' OR TABLE_NAME LIKE '%Cicl%')
            ORDER BY TABLE_NAME
        ");

        if (empty($tables)) {
            $this->warn('Nessuna tabella trovata');
            return self::FAILURE;
        }

        foreach ($tables as $t) {
            $this->line(" - {$t->TABLE_SCHEMA}.{$t->TABLE_NAME}");
        }

        // Step 2: per ogni tabella, cerca colonne testuali e fai match
        $this->info("\n=== Step 2: ricerca match keywords in colonne testuali ===");
        $kwRegex = implode('|', array_map(fn($k) => preg_quote(trim($k), '/'), $kws));

        foreach ($tables as $t) {
            $tableName = $t->TABLE_NAME;
            $cols = DB::connection('onda')->select("
                SELECT COLUMN_NAME, DATA_TYPE
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_NAME = ?
                  AND DATA_TYPE IN ('varchar', 'nvarchar', 'char', 'nchar', 'text', 'ntext')
            ", [$tableName]);

            if (empty($cols)) continue;

            $whereClauses = [];
            $bindings = [];
            foreach ($cols as $c) {
                foreach ($kws as $kw) {
                    $kw = trim($kw);
                    if (!$kw) continue;
                    $whereClauses[] = "[{$c->COLUMN_NAME}] LIKE ?";
                    $bindings[] = '%' . $kw . '%';
                }
            }
            if (empty($whereClauses)) continue;

            $colsList = implode(', ', array_map(fn($c) => "[{$c->COLUMN_NAME}]", $cols));
            $sql = "SELECT TOP {$limit} {$colsList} FROM [{$tableName}] WHERE " . implode(' OR ', $whereClauses);

            try {
                $rows = DB::connection('onda')->select($sql, $bindings);
                if (empty($rows)) continue;

                $this->line("\n--- {$tableName} (" . count($rows) . " match) ---");
                foreach (array_slice($rows, 0, 30) as $r) {
                    $arr = (array) $r;
                    $compact = array_filter($arr, fn($v) => !empty(trim((string)$v)));
                    $line = collect($compact)->map(fn($v, $k) => "{$k}=" . mb_substr((string)$v, 0, 60))->implode(' | ');
                    $this->line("  {$line}");
                }
                if (count($rows) > 30) $this->line("  ... +" . (count($rows) - 30) . " altri");
            } catch (\Throwable $e) {
                $this->error("  ERRORE su {$tableName}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
