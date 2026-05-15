<?php
/**
 * Verifica stato di tutti i sync MES.
 * Mostra ultima esecuzione per ogni componente.
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

function fmt($dt): string {
    if (!$dt) return 'MAI';
    $c = Carbon::parse($dt);
    $min = $c->diffInMinutes(now());
    return $c->format('Y-m-d H:i:s') . " ({$min} min fa)";
}

echo "\n=== SYNC STATUS - " . now()->format('Y-m-d H:i:s') . " ===\n\n";

// 1. Laravel scheduler task log
$logPath = storage_path('logs/scheduler-task.log');
if (file_exists($logPath)) {
    $mtime = filemtime($logPath);
    $age = round((time() - $mtime) / 60, 1);
    echo "[1] Laravel Scheduler task log\n";
    echo "    File: $logPath\n";
    echo "    Ultima modifica: " . date('Y-m-d H:i:s', $mtime) . " ($age min fa)\n";
    if ($age > 5) echo "    !!! WARN: scheduler non gira da $age min\n";
} else {
    echo "[1] Laravel Scheduler: log MANCA ($logPath)\n";
}

// 2. Prinect attività - ultimo record + ultima sync
echo "\n[2] Prinect Sync (every 5 min)\n";
try {
    $ultimaAtt = DB::table('prinect_attivita')->max('updated_at');
    $ultimoStart = DB::table('prinect_attivita')->max('start_time');
    $count24h = DB::table('prinect_attivita')->where('updated_at', '>=', now()->subDay())->count();
    echo "    Ultimo updated_at: " . fmt($ultimaAtt) . "\n";
    echo "    Ultimo start_time: " . fmt($ultimoStart) . "\n";
    echo "    Record updated_at >24h: $count24h\n";
} catch (\Exception $e) {
    echo "    ERRORE: " . $e->getMessage() . "\n";
}

// 3. Fiery (auto-detect tabelle esistenti)
echo "\n[3] Fiery Sync (every 1 min)\n";
$tablesFiery = ['fiery_accounting', 'fiery_jobs', 'fiery_lavori'];
foreach ($tablesFiery as $tbl) {
    try {
        if (!DB::getSchemaBuilder()->hasTable($tbl)) continue;
        $ultimo = DB::table($tbl)->max('updated_at');
        $count = DB::table($tbl)->where('updated_at', '>=', now()->subHour())->count();
        echo "    [$tbl] ultimo: " . fmt($ultimo) . " | record 1h: $count\n";
    } catch (\Exception $e) {
        echo "    [$tbl] errore: " . $e->getMessage() . "\n";
    }
}

// 3b. Fiery Contatori (snapshot 16:50 Mon-Fri)
echo "\n[3b] Contatori Canon iPR V900 (snapshot 16:50 Mon-Fri)\n";
foreach (['contatori_stampante', 'fiery_contatori', 'fiery_contatori_snapshot'] as $tbl) {
    try {
        if (!DB::getSchemaBuilder()->hasTable($tbl)) continue;
        $ultimo = DB::table($tbl)->max('created_at');
        $count = DB::table($tbl)->count();
        echo "    [$tbl] ultimo created_at: " . fmt($ultimo) . " | totali: $count\n";
    } catch (\Exception $e) {
        echo "    [$tbl] errore: " . $e->getMessage() . "\n";
    }
}

// 4. Onda sync - check ordini recenti
echo "\n[4] Onda Sync (every 1 hour)\n";
try {
    $ultimoOrdine = DB::table('ordini')->max('updated_at');
    $count1h = DB::table('ordini')->where('updated_at', '>=', now()->subHours(2))->count();
    echo "    Ultimo ordine updated_at: " . fmt($ultimoOrdine) . "\n";
    echo "    Ordini aggiornati ultime 2h: $count1h\n";
} catch (\Exception $e) {
    echo "    ERRORE: " . $e->getMessage() . "\n";
}

// 5. Excel sync
echo "\n[5] Excel Sync (every 2 min, bidirezionale)\n";
$excelPath = env('EXCEL_SYNC_PATH', 'C:\condivisa\mes\dashboard_mes.xlsx');
if (file_exists($excelPath)) {
    $mtime = filemtime($excelPath);
    $age = round((time() - $mtime) / 60, 1);
    echo "    File: $excelPath\n";
    echo "    Ultima modifica: " . date('Y-m-d H:i:s', $mtime) . " ($age min fa)\n";
    if ($age > 5) echo "    !!! WARN: Excel non aggiornato da $age min\n";
} else {
    echo "    File MANCA: $excelPath\n";
}

// 6. NetTime / Presenze (auto-detect)
echo "\n[6] NetTime / Presenze Sync\n";
foreach (['presenze', 'timbrature', 'nettime_timbrature', 'presenza'] as $tbl) {
    try {
        if (!DB::getSchemaBuilder()->hasTable($tbl)) continue;
        $col = 'updated_at';
        if (!DB::getSchemaBuilder()->hasColumn($tbl, 'updated_at')) {
            $col = DB::getSchemaBuilder()->hasColumn($tbl, 'created_at') ? 'created_at' : 'data';
        }
        $ultimo = DB::table($tbl)->max($col);
        $count = DB::table($tbl)->count();
        echo "    [$tbl] ultimo $col: " . fmt($ultimo) . " | totali: $count\n";
    } catch (\Exception $e) {
        echo "    [$tbl] errore: " . $e->getMessage() . "\n";
    }
}

// 7. Queue worker (Prinect ink job)
echo "\n[7] Queue Worker (Prinect ink jobs)\n";
try {
    if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
        $failed = DB::table('failed_jobs')->count();
        $lastFail = DB::table('failed_jobs')->max('failed_at');
        echo "    Failed jobs totali: $failed\n";
        echo "    Ultimo fallimento: " . fmt($lastFail) . "\n";
    }
    if (DB::getSchemaBuilder()->hasTable('jobs')) {
        $pending = DB::table('jobs')->count();
        echo "    Job in coda: $pending\n";
    }
} catch (\Exception $e) {
    echo "    ERRORE: " . $e->getMessage() . "\n";
}

// 8. Telegram bot (se ha tabella conversation_history o simile)
echo "\n[8] Schedule registrato (php artisan schedule:list)\n";
echo "    Eseguire manualmente: php artisan schedule:list\n";

echo "\n=== FINE ===\n";
