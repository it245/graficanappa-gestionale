<?php

declare(strict_types=1);

namespace App\Modules\Documenti\Services;

use Illuminate\Support\Facades\Log;

/**
 * Layer modulare per sync Excel (dashboard_mes.xlsx, piano_produzione_*.xlsx).
 * NON sostituisce il service legacy: espone una API pulita che il legacy puo usare.
 */
final class ExcelSyncService
{
    private const FILE_DASHBOARD = 'dashboard_mes.xlsx';
    private const STATE_KEY = 'excel_sync_last_mtime';

    public function __construct(
        private readonly string $directory = '',
    ) {
    }

    /**
     * Esporta lo stato corrente del MES verso il file dashboard. Restituisce path.
     * Implementazione minimale: crea file vuoto se assente, delega popolamento al legacy.
     */
    public function exportDashboard(): string
    {
        $path = $this->pathDashboard();
        $dir = dirname($path);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException("Impossibile creare directory: {$dir}");
        }

        if (!is_file($path)) {
            file_put_contents($path, '');
        }

        Log::info('ExcelSync exportDashboard', ['path' => $path]);

        return $path;
    }

    /**
     * Importa modifiche dal file Excel verso il MES.
     * Restituisce il numero di righe modificate (0 se nessun cambiamento).
     */
    public function importChanges(string $path): int
    {
        if (!is_file($path)) {
            throw new \RuntimeException("File non trovato: {$path}");
        }

        $hash = hash_file('sha256', $path) ?: '';
        Log::info('ExcelSync importChanges', ['path' => $path, 'hash' => $hash]);

        // Implementazione lasciata al legacy ExcelSyncService per non duplicare parser PhpSpreadsheet.
        return 0;
    }

    /**
     * Cron-friendly: ritorna true se il file e' cambiato dall'ultima invocazione.
     * Usa cache come storage del mtime precedente.
     */
    public function watchFile(): bool
    {
        $path = $this->pathDashboard();

        if (!is_file($path)) {
            return false;
        }

        $current = (int) filemtime($path);

        if (function_exists('cache')) {
            $previous = (int) cache(self::STATE_KEY, 0);
            cache([self::STATE_KEY => $current], now()->addDays(7));
        } else {
            $previous = 0;
        }

        return $current > $previous;
    }

    private function pathDashboard(): string
    {
        $base = $this->directory !== ''
            ? $this->directory
            : (function_exists('storage_path') ? storage_path('app/excel') : sys_get_temp_dir());

        return rtrim($base, '/\\') . DIRECTORY_SEPARATOR . self::FILE_DASHBOARD;
    }
}
