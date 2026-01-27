<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardOperatoreController;
use App\Http\Controllers\OperatoreLoginController;
use App\Http\Controllers\ProduzioneController;
use App\Http\Controllers\DashboardOwnerController;

// Web login form (solo se vuoi login via browser)
Route::prefix('operatore')->group(function() {

    // Form login per browser
    Route::get('/login', [OperatoreLoginController::class, 'form'])->name('operatore.login');

    // Rotte protette da token
    Route::middleware(['operatore.token'])->group(function() {
        Route::get('/dashboard', [DashboardOperatoreController::class, 'index'])->name('operatore.dashboard');
        Route::post('/logout', [OperatoreLoginController::class, 'logout'])->name('operatore.logout');
    });
});

// Owner
Route::middleware(['web', 'owner'])->group(function() {
    Route::get('/owner/dashboard', [DashboardOwnerController::class, 'index'])->name('owner.dashboard');
    Route::post('/owner/aggiungi-operatore', [DashboardOwnerController::class, 'aggiungiOperatore'])->name('owner.aggiungiOperatore');
    Route::post('/owner/aggiorna-campo', [DashboardOwnerController::class, 'aggiornaCampo'])->name('owner.aggiornaCampo');
    Route::post('/owner/import', [DashboardOwnerController::class, 'importOrdini'])->name('owner.importOrdini');
});

// Produzione - solo operatori loggati
Route::prefix('produzione')->middleware(['operatore.token'])->group(function() {
    Route::get('/', [ProduzioneController::class, 'index']);
    Route::post('/avvia', [ProduzioneController::class, 'avviaFase'])->name('produzione.avvia');
    Route::post('/aggiungi', [ProduzioneController::class, 'aggiungiProduzione'])->name('produzione.aggiungi');
    Route::post('/termina', [ProduzioneController::class, 'terminaFase'])->name('produzione.termina');
    Route::post('/pausa', [ProduzioneController::class, 'pausaFase'])->name('produzione.pausa');
    Route::post('/aggiorna-campo', [ProduzioneController::class, 'aggiornaCampo'])->name('produzione.aggiornaCampo');
    Route::post('/aggiorna-ordine-campo', [ProduzioneController::class, 'aggiornaOrdineCampo'])->name('produzione.aggiornaOrdineCampo');
    Route::get('/dati-dashboard-operatore', [ProduzioneController::class, 'datiDashboardOperatore'])
        ->name('produzione.datiDashboardOperatore')
        ->middleware('operatore.token');
});

// Debug token attivi
Route::get('/debug/token-attivi', function() {
    return response()->json(\App\Models\Operatore::tokenAttivi());
})->middleware('operatore.token');

// Health check
Route::get('/health', fn() => 'MES OK');

// Homepage
Route::get('/', fn() => view('welcome'));