<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardOperatoreController;
use App\Http\Controllers\OperatoreLoginController;
use App\Http\Controllers\ProduzioneController;
use App\Http\Controllers\DashboardOwnerController;

/*
|--------------------------------------------------------------------------
| LOGIN OPERATORE (BROWSER)
|--------------------------------------------------------------------------
*/
Route::prefix('operatore')->group(function () {
    // Form login
    Route::get('/login', [OperatoreLoginController::class, 'form'])
        ->name('operatore.login');
});

// Dashboard view (senza middleware)
Route::get('/dashboard', [DashboardOperatoreController::class, 'index'])
    ->name('operatore.dashboard');

/*
|--------------------------------------------------------------------------
| API DASHBOARD OPERATORE (JSON + PRODUZIONE)
|--------------------------------------------------------------------------
| Tutte le chiamate JS → token-based
*/
Route::prefix('operatore')->middleware('operatore.token')->group(function () {

    Route::get('/dashboard-api', [DashboardOperatoreController::class, 'index'])
        ->name('operatore.dashboard.api');

    Route::post('/logout', [OperatoreLoginController::class, 'logout'])
        ->name('operatore.logout');

    // Produzione API
    Route::prefix('produzione')->group(function () {
        Route::post('/avvia', [ProduzioneController::class, 'avviaFase'])->name('produzione.avvia');
        Route::post('/aggiungi', [ProduzioneController::class, 'aggiungiProduzione'])->name('produzione.aggiungi');
        Route::post('/termina', [ProduzioneController::class, 'terminaFase'])->name('produzione.termina');
        Route::post('/pausa', [ProduzioneController::class, 'pausaFase'])->name('produzione.pausa');
        Route::post('/aggiorna-campo', [ProduzioneController::class, 'aggiornaCampo'])->name('produzione.aggiornaCampo');
        Route::post('/aggiorna-ordine-campo', [ProduzioneController::class, 'aggiornaOrdineCampo'])->name('produzione.aggiornaOrdineCampo');
        Route::get('/dati-dashboard-operatore', [ProduzioneController::class, 'datiDashboardOperatore'])->name('produzione.datiDashboardOperatore');
    });
});

/*
|--------------------------------------------------------------------------
| OWNER (resta invariato)
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'owner'])->group(function () {
    Route::get('/owner/dashboard', [DashboardOwnerController::class, 'index'])->name('owner.dashboard');
    Route::post('/owner/aggiungi-operatore', [DashboardOwnerController::class, 'aggiungiOperatore'])->name('owner.aggiungiOperatore');
    Route::post('/owner/aggiorna-campo', [DashboardOwnerController::class, 'aggiornaCampo'])->name('owner.aggiornaCampo');
    Route::post('/owner/import', [DashboardOwnerController::class, 'importOrdini'])->name('owner.importOrdini');
});

/* Altro */
Route::get('/health', fn () => 'MES OK');
Route::get('/', fn () => view('welcome'));