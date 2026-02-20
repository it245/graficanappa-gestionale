<?php

namespace App\Http\Services;

use App\Exports\DashboardMesExport;
use App\Models\Ordine;
use App\Models\OrdineFase;
use App\Services\FaseStatoService;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Support\Facades\Log;

class ExcelSyncService
{
    private static function getExcelDir(): string
    {
        $path = env('EXCEL_SYNC_PATH');
        if ($path && $path !== '') {
            return rtrim($path, '/\\');
        }
        return storage_path('app/excel_sync');
    }

    private static function getExcelPath(): string
    {
        return self::getExcelDir() . DIRECTORY_SEPARATOR . 'dashboard_mes.xlsx';
    }

    private static function getTimestampPath(): string
    {
        return self::getExcelDir() . DIRECTORY_SEPARATOR . '.last_sync_timestamp';
    }

    /**
     * Esporta i dati della dashboard in un file Excel.
     */
    public static function exportToExcel(): void
    {
        if (!env('EXCEL_SYNC_ENABLED', false)) {
            return;
        }

        $dir = self::getExcelDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = self::getExcelPath();

        // Sopprime deprecation warnings di PhpSpreadsheet su PHP 8.2+
        $previousReporting = error_reporting(E_ALL & ~E_DEPRECATED);

        try {
            $content = Excel::raw(new DashboardMesExport, \Maatwebsite\Excel\Excel::XLSX);
            file_put_contents($path, $content);

            // Salva timestamp dell'ultima scrittura
            file_put_contents(self::getTimestampPath(), time());
        } catch (\Throwable $e) {
            // File aperto in Excel da un utente (lock Windows) → skip silenzioso
            Log::debug('ExcelSync export skip: ' . $e->getMessage());
        } finally {
            error_reporting($previousReporting);
        }
    }

    /**
     * Importa le modifiche dal file Excel nel database.
     */
    public static function importFromExcel(): void
    {
        if (!env('EXCEL_SYNC_ENABLED', false)) {
            return;
        }

        $path = self::getExcelPath();
        if (!file_exists($path)) {
            return;
        }

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Exception $e) {
            Log::debug('ExcelSync import skip: ' . $e->getMessage());
            return;
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, false, false, true);

        // Salta riga header (riga 1)
        $isFirst = true;
        foreach ($rows as $row) {
            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            $id = $row['A'] ?? null;
            if (!$id || !is_numeric($id)) {
                continue;
            }

            $fase = OrdineFase::with('ordine')->find((int) $id);
            if (!$fase || $fase->stato >= 3) {
                continue;
            }

            $ordine = $fase->ordine;
            if (!$ordine) {
                continue;
            }

            $changed = false;
            $ordineChanged = false;

            // C - Stato (editabile)
            $excelStato = self::normalizeValue($row['C'] ?? null);
            if ($excelStato !== '' && is_numeric($excelStato)) {
                $nuovoStato = (int) $excelStato;
                if ($nuovoStato !== (int) $fase->stato && $nuovoStato < 3) {
                    $fase->stato = $nuovoStato;
                    $changed = true;
                }
            }

            // D - Cliente
            if (self::isTextChanged($row['D'] ?? null, $ordine->cliente_nome)) {
                $ordine->cliente_nome = self::normalizeValue($row['D'] ?? null);
                $ordineChanged = true;
            }

            // E - Cod Articolo
            if (self::isTextChanged($row['E'] ?? null, $ordine->cod_art)) {
                $ordine->cod_art = self::normalizeValue($row['E'] ?? null);
                $ordineChanged = true;
            }

            // F - Descrizione
            if (self::isTextChanged($row['F'] ?? null, $ordine->descrizione)) {
                $ordine->descrizione = self::normalizeValue($row['F'] ?? null);
                $ordineChanged = true;
            }

            // G - Qta
            if (self::isNumericChanged($row['G'] ?? null, $ordine->qta_richiesta)) {
                $ordine->qta_richiesta = self::parseNumeric($row['G'] ?? null);
                $ordineChanged = true;
            }

            // H - UM
            if (self::isTextChanged($row['H'] ?? null, $ordine->um)) {
                $ordine->um = self::normalizeValue($row['H'] ?? null);
                $ordineChanged = true;
            }

            // I - Priorita
            if (self::isNumericChanged($row['I'] ?? null, $fase->priorita)) {
                $fase->priorita = self::parseNumeric($row['I'] ?? null);
                $changed = true;
            }

            // J - Data Registrazione
            $excelDataReg = self::parseExcelDate($row['J'] ?? null);
            $dbDataReg = $ordine->data_registrazione ? Carbon::parse($ordine->data_registrazione)->format('Y-m-d') : null;
            if (self::isDateChanged($excelDataReg, $dbDataReg)) {
                $ordine->data_registrazione = $excelDataReg;
                $ordineChanged = true;
            }

            // K - Data Prevista Consegna
            $excelDataConsegna = self::parseExcelDate($row['K'] ?? null);
            $dbDataConsegna = $ordine->data_prevista_consegna ? Carbon::parse($ordine->data_prevista_consegna)->format('Y-m-d') : null;
            if (self::isDateChanged($excelDataConsegna, $dbDataConsegna)) {
                $ordine->data_prevista_consegna = $excelDataConsegna;
                $ordineChanged = true;

                // Propaga a tutti gli ordini della stessa commessa
                Ordine::where('commessa', $ordine->commessa)
                    ->where('id', '!=', $ordine->id)
                    ->update(['data_prevista_consegna' => $excelDataConsegna]);

                FaseStatoService::ricalcolaStati($fase->ordine_id);
            }

            // L - Cod Carta
            if (self::isTextChanged($row['L'] ?? null, $ordine->cod_carta)) {
                $ordine->cod_carta = self::normalizeValue($row['L'] ?? null);
                $ordineChanged = true;
            }

            // M - Carta
            if (self::isTextChanged($row['M'] ?? null, $ordine->carta)) {
                $ordine->carta = self::normalizeValue($row['M'] ?? null);
                $ordineChanged = true;
            }

            // N - Qta Carta
            if (self::isNumericChanged($row['N'] ?? null, $ordine->qta_carta)) {
                $ordine->qta_carta = self::parseNumeric($row['N'] ?? null);
                $ordineChanged = true;
            }

            // O - UM Carta
            if (self::isTextChanged($row['O'] ?? null, $ordine->UM_carta)) {
                $ordine->UM_carta = self::normalizeValue($row['O'] ?? null);
                $ordineChanged = true;
            }

            // S - Qta Prod
            $excelQtaProd = self::parseNumeric($row['S'] ?? null);
            if (self::isNumericChanged($row['S'] ?? null, $fase->qta_prod)) {
                $fase->qta_prod = $excelQtaProd;
                $changed = true;
            }

            // T - Note
            if (self::isTextChanged($row['T'] ?? null, $fase->note)) {
                $fase->note = self::normalizeValue($row['T'] ?? null);
                $changed = true;
            }

            // U - Data Inizio
            $excelDataInizio = self::parseExcelDateTime($row['U'] ?? null);
            $dbDataInizio = $fase->getAttributes()['data_inizio'] ?? null;
            $dbDataInizio = $dbDataInizio ? Carbon::parse($dbDataInizio)->format('Y-m-d H:i:s') : null;
            if (self::isDateTimeChanged($excelDataInizio, $dbDataInizio)) {
                $fase->data_inizio = $excelDataInizio;
                $changed = true;
            }

            // V - Data Fine
            $excelDataFine = self::parseExcelDateTime($row['V'] ?? null);
            $dbDataFine = $fase->getAttributes()['data_fine'] ?? null;
            $dbDataFine = $dbDataFine ? Carbon::parse($dbDataFine)->format('Y-m-d H:i:s') : null;
            if (self::isDateTimeChanged($excelDataFine, $dbDataFine)) {
                $fase->data_fine = $excelDataFine;
                $changed = true;
            }

            if ($changed) {
                $fase->save();

                // Controlla completamento se qta_prod cambiata
                if (self::isNumericChanged($row['S'] ?? null, $fase->getOriginal('qta_prod'))) {
                    FaseStatoService::controllaCompletamento($fase->id);
                }

                // Ricalcola stati se stato cambiato
                if ($fase->wasChanged('stato')) {
                    FaseStatoService::ricalcolaStati($fase->ordine_id);
                }
            }

            if ($ordineChanged) {
                $ordine->save();
            }
        }
    }

    /**
     * Sync condizionato: importa solo se il file e stato modificato dall'ultimo sync.
     */
    public static function syncIfModified(): void
    {
        if (!env('EXCEL_SYNC_ENABLED', false)) {
            return;
        }

        $path = self::getExcelPath();

        if (!file_exists($path)) {
            self::exportToExcel();
            return;
        }

        $timestampPath = self::getTimestampPath();
        $lastSync = file_exists($timestampPath) ? (int) file_get_contents($timestampPath) : 0;
        $fileModified = filemtime($path);

        if ($fileModified > $lastSync) {
            self::importFromExcel();
            // Aggiorna timestamp dopo import
            file_put_contents($timestampPath, time());
        }
    }

    // ==================== Helper di normalizzazione ====================

    private static function normalizeValue($value): string
    {
        if ($value === null || $value === '-' || $value === '') {
            return '';
        }
        return trim((string) $value);
    }

    private static function parseNumeric($value): float
    {
        if ($value === null || $value === '' || $value === '-') {
            return 0;
        }
        return (float) str_replace(',', '.', (string) $value);
    }

    private static function isTextChanged($excelValue, $dbValue): bool
    {
        return self::normalizeValue($excelValue) !== self::normalizeValue($dbValue);
    }

    private static function isNumericChanged($excelValue, $dbValue): bool
    {
        $a = self::parseNumeric($excelValue);
        $b = self::parseNumeric($dbValue);
        return abs($a - $b) > 0.001;
    }

    private static function isDateChanged(?string $excelDate, ?string $dbDate): bool
    {
        $a = $excelDate ?: '';
        $b = $dbDate ?: '';
        return $a !== $b;
    }

    private static function isDateTimeChanged(?string $excelDate, ?string $dbDate): bool
    {
        $a = $excelDate ?: '';
        $b = $dbDate ?: '';
        return $a !== $b;
    }

    /**
     * Converte un valore Excel (serial number o stringa italiana dd/mm/yyyy) in Y-m-d.
     */
    private static function parseExcelDate($value): ?string
    {
        if ($value === null || $value === '' || $value === '-') {
            return null;
        }

        // Serial number Excel
        if (is_numeric($value) && (float) $value > 25000) {
            try {
                $date = ExcelDate::excelToDateTimeObject((float) $value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                // fallthrough
            }
        }

        $str = trim((string) $value);

        // Formato dd/mm/yyyy
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $str, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // Formato yyyy-mm-dd (gia ok)
        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $str)) {
            return $str;
        }

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Converte un valore Excel (serial number o stringa italiana dd/mm/yyyy HH:ii:ss) in Y-m-d H:i:s.
     */
    private static function parseExcelDateTime($value): ?string
    {
        if ($value === null || $value === '' || $value === '-') {
            return null;
        }

        // Serial number Excel (con decimali per l'ora)
        if (is_numeric($value) && (float) $value > 25000) {
            try {
                $date = ExcelDate::excelToDateTimeObject((float) $value);
                return $date->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // fallthrough
            }
        }

        $str = trim((string) $value);

        // Formato dd/mm/yyyy HH:ii:ss
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})\s+(\d{1,2}):(\d{2}):(\d{2})$#', $str, $m)) {
            return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $m[3], $m[2], $m[1], $m[4], $m[5], $m[6]);
        }

        // Formato dd/mm/yyyy (senza ora)
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $str, $m)) {
            return sprintf('%04d-%02d-%02d 00:00:00', $m[3], $m[2], $m[1]);
        }

        // Formato yyyy-mm-dd HH:ii:ss
        if (preg_match('#^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$#', $str)) {
            return $str;
        }

        try {
            return Carbon::parse($str)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }
}
