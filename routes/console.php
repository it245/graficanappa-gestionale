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

// Sync automatico Onda ogni ora (lun-sab, 6:00-22:00)
Schedule::command('onda:sync')->hourly()->between('6:00', '22:00')->days([1, 2, 3, 4, 5, 6]);

// Sync bidirezionale Excel ↔ DB ogni 2 minuti (lun-sab, 6:00-22:00)
Schedule::command('excel:sync')->everyTwoMinutes()->between('6:00', '22:00')->days([1, 2, 3, 4, 5, 6])->withoutOverlapping();