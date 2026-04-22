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

// Controllo consegne BRT in ritardo — DISABILITATO
// Schedule::command('brt:check-ritardi')->dailyAt('09:00');

// Snapshot contatori Canon iPR V900 via SNMP (ogni giorno alle 23:55)
Schedule::command('fiery:snapshot-contatori')->dailyAt('23:55');

// Sync presenze NetTime ogni minuto (lun-ven 5:00-23:00)
Schedule::command('presenze:sync')->everyMinute()->weekdays()->between('5:00', '23:00')->withoutOverlapping();

Schedule::command('presenze:export-excel')->everyFifteenMinutes()->weekdays()->between('5:00', '23:00')->withoutOverlapping();

// Pulizia audit log: mantieni ultimi 90 giorni
Schedule::command('audit:pulisci')->dailyAt('03:00');

// Match automatico cliché ogni 10 minuti (dopo sync Onda)
Schedule::command('cliche:match')->everyTenMinutes()->withoutOverlapping();

// Scheduler Mossa 37 — DISABILITATO (in attesa approvazione capo)
// Schedule::command('scheduler:run')->everyFifteenMinutes()->weekdays()->between('6:00', '22:00')->withoutOverlapping();

// ===== PIANO PRODUZIONE EMAIL MATTUTINA =====
// Invia piano produzione con Excel allegato ai capi reparto ogni mattina
// Abilitato/disabilitato da .env: PIANO_EMAIL_ENABLED=true|false
// Orario: PIANO_EMAIL_ORA=06:00 (default)
// Destinatari: PIANO_EMAIL_TO="mario@x.it,luigi@y.it" (lista separata da virgola)
if (env('PIANO_EMAIL_ENABLED', false)) {
    $ora = env('PIANO_EMAIL_ORA', '06:00');
    $destinatari = env('PIANO_EMAIL_TO', 'anappa@graficanappa.com');
    Schedule::command("scheduler:run --email --to={$destinatari}")
        ->weekdays()
        ->dailyAt($ora)
        ->withoutOverlapping();
}

// ===== ALERT COMMESSE A RISCHIO =====
// Notifica commesse critiche (scadute o <=2gg alla consegna) ogni mattina 7:30
// Abilitato da .env: ALERT_RITARDI_ENABLED=true|false
if (env('ALERT_RITARDI_ENABLED', false)) {
    Schedule::command('ritardi:alert')
        ->weekdays()
        ->dailyAt('07:30')
        ->withoutOverlapping();
}