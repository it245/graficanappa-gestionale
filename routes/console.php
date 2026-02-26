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