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

    private static function getHashPath(): string
    {
        return self::getExcelDir() . DIRECTORY_SEPARATOR . '.last_sync_hash';
    }

    private static function getExportedIdsPath(): string
    {
        return self::getExcelDir() . DIRECTORY_SEPARATOR . '.last_exported_ids';
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
            // Salva gli ID esportati PRIMA di scrivere il file (per deletion sicura)
            $exportedIds = OrdineFase::where(fn($q) => $q->where('stato', '<', 4)->orWhere('stato', 5))->pluck('id')->toArray();
            file_put_contents(self::getExportedIdsPath(), json_encode($exportedIds));

            $content = Excel::raw(new DashboardMesExport, \Maatwebsite\Excel\Excel::XLSX);
            file_put_contents($path, $content);

            // Salva hash del file esportato (per rilevare modifiche esterne)
            file_put_contents(self::getHashPath(), md5($content));
            // Salva anche timestamp per backward compatibility
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
    public static function importFromExcel(): bool
    {
        if (!env('EXCEL_SYNC_ENABLED', false)) {
            return false;
        }

        $path = self::getExcelPath();
        if (!file_exists($path)) {
            return false;
        }

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Exception $e) {
            Log::warning('ExcelSync import fallito: ' . $e->getMessage());
            return false;
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, false, false, true);

        // Salta riga header (riga 1)
        $isFirst = true;
        $idTrovati = [];
        $commesseDatePropagated = []; // Evita che righe successive revertano la propagazione data

        foreach ($rows as $row) {
            if ($isFirst) {
                $isFirst = false;
                continue;
            }

            $id = $row['A'] ?? null;

            // Nuova riga: colonna A vuota ma colonna B (commessa) presente
            if (!$id || !is_numeric($id)) {
                $commessa = self::normalizeValue($row['B'] ?? null);
                if ($commessa !== '') {
                    $ordine = Ordine::create([
                        'commessa' => $commessa,
                        'cliente_nome' => self::normalizeValue($row['D'] ?? null),
                        'cod_art' => self::normalizeValue($row['E'] ?? null),
                        'descrizione' => self::normalizeValue($row['F'] ?? null),
                        'qta_richiesta' => self::parseNumeric($row['G'] ?? null),
                        'um' => self::normalizeValue($row['H'] ?? null) ?: 'FG',
                        'data_registrazione' => self::parseExcelDate($row['J'] ?? null) ?? now()->toDateString(),
                        'data_prevista_consegna' => self::parseExcelDate($row['K'] ?? null),
                        'cod_carta' => self::normalizeValue($row['L'] ?? null),
                        'carta' => self::normalizeValue($row['M'] ?? null),
                        'qta_carta' => self::parseNumeric($row['N'] ?? null),
                        'UM_carta' => self::normalizeValue($row['O'] ?? null),
                        'note_prestampa' => self::normalizeValue($row['P'] ?? null),
                        'responsabile' => self::normalizeValue($row['Q'] ?? null),
                        'commento_produzione' => self::normalizeValue($row['R'] ?? null),
                        'stato' => 0,
                    ]);

                    $excelStato = self::normalizeValue($row['C'] ?? null);
                    // Campi nuovi ordine (Z-AD)
                    $ordCliente = self::normalizeValue($row['Z'] ?? null);
                    if ($ordCliente !== '') $ordine->ordine_cliente = $ordCliente;
                    $numDdt = self::normalizeValue($row['AA'] ?? null);
                    if ($numDdt !== '') $ordine->numero_ddt_vendita = $numDdt;
                    $vettDdt = self::normalizeValue($row['AB'] ?? null);
                    if ($vettDdt !== '') $ordine->vettore_ddt = $vettDdt;
                    $qtaDdt = self::parseNumeric($row['AC'] ?? null);
                    if ($qtaDdt > 0) $ordine->qta_ddt_vendita = $qtaDdt;
                    $noteFS = self::normalizeValue($row['AD'] ?? null);
                    if ($noteFS !== '') $ordine->note_fasi_successive = $noteFS;
                    $ordine->save();

                    $nuovaFase = OrdineFase::create([
                        'ordine_id' => $ordine->id,
                        'fase' => self::normalizeValue($row['S'] ?? null) ?: '-',
                        'stato' => ($excelStato !== '' && is_numeric($excelStato)) ? (int) $excelStato : 0,
                        'qta_prod' => self::parseNumeric($row['V'] ?? null),
                        'note' => self::normalizeValue($row['W'] ?? null),
                        'priorita' => self::parseNumeric($row['I'] ?? null),
                        'manuale' => true,
                    ]);
                    $idTrovati[] = $nuovaFase->id;
                }
                continue;
            }

            $idTrovati[] = (int) $id;

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
                $nuovaPriorita = self::parseNumeric($row['I'] ?? null);
                Log::info("ExcelSync: priorità fase #{$id} cambiata: {$fase->priorita} → {$nuovaPriorita}");
                $fase->priorita = $nuovaPriorita;
                $fase->priorita_manuale = true;
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
            // Skip se la data di questa commessa è già stata propagata da un'altra riga
            if (!isset($commesseDatePropagated[$ordine->commessa]) && self::isDateChanged($excelDataConsegna, $dbDataConsegna)) {
                $ordine->data_prevista_consegna = $excelDataConsegna;
                $ordineChanged = true;

                // Propaga a tutti gli ordini della stessa commessa
                $propagati = Ordine::where('commessa', $ordine->commessa)
                    ->where('id', '!=', $ordine->id)
                    ->update(['data_prevista_consegna' => $excelDataConsegna]);

                $commesseDatePropagated[$ordine->commessa] = true;

                Log::info("ExcelSync: data consegna cambiata per commessa {$ordine->commessa}: {$dbDataConsegna} → {$excelDataConsegna} (propagata a {$propagati} ordini)");

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

            // P - Note Prestampa
            if (self::isTextChanged($row['P'] ?? null, $ordine->note_prestampa)) {
                $ordine->note_prestampa = self::normalizeValue($row['P'] ?? null);
                $ordineChanged = true;
            }

            // Q - Responsabile
            if (self::isTextChanged($row['Q'] ?? null, $ordine->responsabile)) {
                $ordine->responsabile = self::normalizeValue($row['Q'] ?? null);
                $ordineChanged = true;
            }

            // R - Commento Produzione
            if (self::isTextChanged($row['R'] ?? null, $ordine->commento_produzione)) {
                $ordine->commento_produzione = self::normalizeValue($row['R'] ?? null);
                $ordineChanged = true;
            }

            // V - Qta Prod
            $excelQtaProd = self::parseNumeric($row['V'] ?? null);
            if (self::isNumericChanged($row['V'] ?? null, $fase->qta_prod)) {
                $fase->qta_prod = $excelQtaProd;
                $changed = true;
            }

            // W - Note
            if (self::isTextChanged($row['W'] ?? null, $fase->note)) {
                $fase->note = self::normalizeValue($row['W'] ?? null);
                $changed = true;
            }

            // Z - Ordine Cliente
            if (self::isTextChanged($row['Z'] ?? null, $ordine->ordine_cliente)) {
                $ordine->ordine_cliente = self::normalizeValue($row['Z'] ?? null);
                $ordineChanged = true;
            }

            // AA - N. DDT Vendita
            if (self::isTextChanged($row['AA'] ?? null, $ordine->numero_ddt_vendita)) {
                $ordine->numero_ddt_vendita = self::normalizeValue($row['AA'] ?? null);
                $ordineChanged = true;
            }

            // AB - Vettore DDT
            if (self::isTextChanged($row['AB'] ?? null, $ordine->vettore_ddt)) {
                $ordine->vettore_ddt = self::normalizeValue($row['AB'] ?? null);
                $ordineChanged = true;
            }

            // AC - Qta DDT
            if (self::isNumericChanged($row['AC'] ?? null, $ordine->qta_ddt_vendita)) {
                $ordine->qta_ddt_vendita = self::parseNumeric($row['AC'] ?? null);
                $ordineChanged = true;
            }

            // AD - Note Fasi Successive
            if (self::isTextChanged($row['AD'] ?? null, $ordine->note_fasi_successive)) {
                $ordine->note_fasi_successive = self::normalizeValue($row['AD'] ?? null);
                $ordineChanged = true;
            }

            // AE, AF, AG (Colori, Fustella, Esterno) → calcolati, skip import

            // X - Data Inizio
            $excelDataInizio = self::parseExcelDateTime($row['X'] ?? null);
            $dbDataInizio = $fase->getAttributes()['data_inizio'] ?? null;
            $dbDataInizio = $dbDataInizio ? Carbon::parse($dbDataInizio)->format('Y-m-d H:i:s') : null;
            if (self::isDateTimeChanged($excelDataInizio, $dbDataInizio)) {
                $fase->data_inizio = $excelDataInizio;
                $changed = true;
            }

            // Y - Data Fine
            $excelDataFine = self::parseExcelDateTime($row['Y'] ?? null);
            $dbDataFine = $fase->getAttributes()['data_fine'] ?? null;
            $dbDataFine = $dbDataFine ? Carbon::parse($dbDataFine)->format('Y-m-d H:i:s') : null;
            if (self::isDateTimeChanged($excelDataFine, $dbDataFine)) {
                $fase->data_fine = $excelDataFine;
                $changed = true;
            }

            if ($changed) {
                $fase->save();

                // Controlla completamento se qta_prod cambiata
                if (self::isNumericChanged($row['V'] ?? null, $fase->getOriginal('qta_prod'))) {
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

        // --- Eliminazione righe rimosse dall'Excel ---
        // Solo gli ID che erano nell'ultimo export ma non sono più nel file
        $exportedIdsPath = self::getExportedIdsPath();
        if (!empty($idTrovati) && file_exists($exportedIdsPath)) {
            $lastExportedIds = json_decode(file_get_contents($exportedIdsPath), true) ?: [];
            // ID rimossi = erano nell'export ma non trovati nell'import
            $removedIds = array_diff($lastExportedIds, $idTrovati);

            foreach ($removedIds as $removedId) {
                $faseDaEliminare = OrdineFase::find($removedId);
                if ($faseDaEliminare && $faseDaEliminare->stato < 3) {
                    $ordineId = $faseDaEliminare->ordine_id;
                    Log::info("ExcelSync: eliminata fase #{$removedId} (rimossa dall'Excel)");
                    $faseDaEliminare->operatori()->detach();
                    $faseDaEliminare->delete();

                    // Se l'ordine resta senza fasi, elimina anche l'ordine
                    if (OrdineFase::where('ordine_id', $ordineId)->count() === 0) {
                        Ordine::where('id', $ordineId)->delete();
                        Log::info("ExcelSync: eliminato ordine #{$ordineId} (senza fasi)");
                    }
                }
            }
        }

        return true;
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

        // Confronto basato su hash del contenuto (più affidabile di filemtime su rete/Windows)
        $hashPath = self::getHashPath();
        $lastHash = file_exists($hashPath) ? file_get_contents($hashPath) : '';
        $currentHash = md5_file($path);

        if ($currentHash !== $lastHash) {
            Log::info('ExcelSync: rilevata modifica file Excel, avvio import...');
            self::importFromExcel();
            // NON aggiornare l'hash qui: sarà aggiornato dall'export successivo
            // Così se l'import fallisce, verrà ritentato al prossimo ciclo
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

        // Formato dd/mm/yyyy HH:ii (senza secondi)
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})\s+(\d{1,2}):(\d{2})$#', $str, $m)) {
            return sprintf('%04d-%02d-%02d %02d:%02d:00', $m[3], $m[2], $m[1], $m[4], $m[5]);
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
