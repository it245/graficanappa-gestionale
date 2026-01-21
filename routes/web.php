<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardOperatoreController;
use App\Http\Controllers\OperatoreLoginController;
use App\Http\Controllers\ProduzioneController;

// Rotta di test rapido /health
Route::get('/health', function () {
    return 'MES OK';
});

// Rotta principale welcome
Route::get('/', function () {
    return view('welcome');
});

// Rotte operatori (protette dal middleware)
Route::middleware(['web', 'operatore.auth'])->group(function () {
    Route::get('/operatore/dashboard', [DashboardOperatoreController::class, 'index'])
        ->name('operatore.dashboard');

    Route::post('/operatore/logout', [OperatoreLoginController::class, 'logout'])
        ->name('operatore.logout');
});

// Login operatori
Route::get('/operatore/login', [OperatoreLoginController::class, 'form'])
    ->name('operatore.login');

Route::post('/operatore/login', [OperatoreLoginController::class, 'login'])
    ->name('operatore.login.post');

// Dashboard superadmin
Route::get('/dashboard/admin', [DashboardController::class, 'superadmin'])
    ->name('dashboard.superadmin');

// Rotte produzione
Route::get('/produzione', [ProduzioneController::class, 'index']);
Route::post('/produzione/avvia', [ProduzioneController::class, 'avviaFase'])->name('produzione.avvia');
Route::post('/produzione/aggiungi', [ProduzioneController::class, 'aggiungiProduzione'])->name('produzione.aggiungi');
Route::post('/produzione/termina', [ProduzioneController::class, 'terminaFase'])->name('produzione.termina');
Route::post('/produzione/pausa', [ProduzioneController::class, 'pausaFase'])->name('produzione.pausa');
Route::post('/produzione/aggiorna-campo',[ProduzioneController::class,'aggiornaCampo'])->name('produzione.aggiornaCampo');
Route::post('/produzione/aggiorna-ordine-campo',[ProduzioneController::class,'aggiornaOrdineCampo'])->name('produzione.aggiornaOrdineCampo');