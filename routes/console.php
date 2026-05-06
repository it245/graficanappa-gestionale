<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\OrdineFase;
use App\Http\Controllers\DashboardOwnerController;

Artisan::command('priorita:ricalcola', function () {
    $this->info('Ricalcolo priorità in corso...');

    $controller = app(DashboardOwnerController::class);

    $fasi = OrdineFase::with('ordine')->get();

    foreach ($fasi as $fase) {
        $controller->calcolaOreEPriorita($fase);
    }

    $this->info('Ricalcolo completato.');
})->describe('Ricalcola tutte le priorità degli ordini');

// Sync automatico Onda ogni ora (h24)
Schedule::command('onda:sync')->hourly();

// Sync bidirezionale Excel ↔ DB ogni 2 minuti (h24)
Schedule::command('excel:sync')->everyTwoMinutes()->withoutOverlapping();

// Sync automatico Fiery ogni minuto (h24)
Schedule::command('fiery:sync')->everyMinute()->withoutOverlapping();

// Sync attivita Prinect XL106 ogni 5 minuti (storico 7gg)
Schedule::command('prinect:sync-attivita')->everyFiveMinutes()->withoutOverlapping();

// Sync principale Prinect — chiusura fasi stampa offset a stato 3 ogni 2 minuti
Schedule::command('prinect:sync')->everyTwoMinutes()->withoutOverlapping();

// Controllo consegne BRT in ritardo — DISABILITATO
// Schedule::command('brt:check-ritardi')->dailyAt('09:00');

// Snapshot contatori Canon iPR V900 via SNMP (ore 16:55 lun-ven, prima spegnimento 17:00)
Schedule::command('fiery:snapshot-contatori')->weekdays()->dailyAt('16:55');

// Report mensile contatori V900 — ultimo giorno del mese alle 17:00 (dopo snapshot 16:55)
// Destinatari da .env: REPORT_CONTATORI_TO="it@graficanappa.com"
$reportTo = env('REPORT_CONTATORI_TO', 'it@graficanappa.com');
Schedule::command("fiery:export-contatori --mese-corrente --email={$reportTo}")
    ->lastDayOfMonth('17:00')
    ->withoutOverlapping();

// Force export NetTime su PC .34 via WinRM ogni 5 min + sync presenze
// (no più dipendenza da Marco che fa export manuale)
Schedule::command('nettime:export-remote --sync')->everyFiveMinutes()->weekdays()->between('5:00', '23:00')->withoutOverlapping();

// Sync presenze NetTime ogni minuto (lun-ven 5:00-23:00) - legge il file BKP esportato
Schedule::command('presenze:sync')->everyMinute()->weekdays()->between('5:00', '23:00')->withoutOverlapping();

Schedule::command('presenze:export-excel')->everyFifteenMinutes()->weekdays()->between('5:00', '23:00')->withoutOverlapping();

// Pulizia audit log: mantieni ultimi 90 giorni
Schedule::command('audit:pulisci')->dailyAt('03:00');

// Match automatico cliché ogni 10 minuti (dopo sync Onda)
Schedule::command('cliche:match')->everyTenMinutes()->withoutOverlapping();

// Scheduler Mossa 37 — DISABILITATO (in attesa approvazione capo)
// Schedule::command('scheduler:run')->everyFifteenMinutes()->weekdays()->between('6:00', '22:00')->withoutOverlapping();

// Piano produzione serale via email — DISABILITATO
// Schedule::command('scheduler:run --email')->weekdays()->dailyAt('22:00');