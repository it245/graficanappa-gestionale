<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardOperatoreController;
use App\Http\Controllers\OperatoreLoginController;
use App\Http\Controllers\ProduzioneController;
use App\Http\Controllers\DashboardOwnerController;
use App\Http\Controllers\PrinectController;
use App\Http\Controllers\DashboardSpedizioneController;
use App\Http\Controllers\CostiMarginiController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\DashboardAdminController;

// Operatori
Route::prefix('operatore')->group(function() {
     Route::get('/login', [OperatoreLoginController::class, 'form'])->name('operatore.login');
    Route::post('/login', [OperatoreLoginController::class, 'login'])->name('operatore.login.post');

    Route::middleware(['operatore.auth'])->group(function() {
        Route::get('dashboard', [DashboardOperatoreController::class, 'index'])->name('operatore.dashboard');
        Route::post('/logout', [OperatoreLoginController::class, 'logout'])->name('operatore.logout');
    });
});

// Owner
Route::middleware(['web', 'owner'])->group(function() {
    Route::get('/owner/dashboard', [DashboardOwnerController::class, 'index'])->name('owner.dashboard');
Route::post('/owner/aggiorna-campo', [DashboardOwnerController::class, 'aggiornaCampo'])->name('owner.aggiornaCampo');
Route::post('/owner/import', [DashboardOwnerController::class, 'importOrdini'])->name('owner.importOrdini');
Route::post('/owner/sync-onda', [DashboardOwnerController::class, 'syncOnda'])->name('owner.syncOnda');
Route::post('/owner/aggiungi-riga', [DashboardOwnerController::class, 'aggiungiRiga'])->name('owner.aggiungiRiga');
Route::get('owner/fasi-terminate',[DashboardOwnerController::class, 'fasiTerminate'])->name('owner.fasiTerminate');
Route::get('/owner/commessa/{commessa}', [DashboardOwnerController::class, 'dettaglioCommessa'])->name('owner.dettaglioCommessa');
Route::post('/owner/aggiorna-stato', [DashboardOwnerController::class, 'aggiornaStato'])->name('owner.aggiornaStato');
Route::post('/owner/ricalcola-stati', [DashboardOwnerController::class, 'ricalcolaStati'])->name('owner.ricalcolaStati');
Route::post('/owner/elimina-fase', [DashboardOwnerController::class, 'eliminaFase'])->name('owner.eliminaFase');
Route::get('/owner/scheduling', [DashboardOwnerController::class, 'scheduling'])->name('owner.scheduling');
Route::get('/owner/excel-download', [DashboardOwnerController::class, 'downloadExcel'])->name('owner.downloadExcel');


});

// Admin — login pubblico
Route::get('/admin/login', [AdminLoginController::class, 'form'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->name('admin.login.post');

// Admin — area protetta
Route::middleware(['admin'])->prefix('admin')->group(function() {
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');
    Route::get('/dashboard', [DashboardAdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/operatore/nuovo', [DashboardAdminController::class, 'crea'])->name('admin.operatore.crea');
    Route::post('/operatore', [DashboardAdminController::class, 'salva'])->name('admin.operatore.salva');
    Route::get('/operatore/{id}/modifica', [DashboardAdminController::class, 'modifica'])->name('admin.operatore.modifica');
    Route::post('/operatore/{id}', [DashboardAdminController::class, 'aggiorna'])->name('admin.operatore.aggiorna');
    Route::post('/operatore/{id}/toggle', [DashboardAdminController::class, 'toggleAttivo'])->name('admin.operatore.toggleAttivo');
    Route::get('/statistiche-operatori', [DashboardAdminController::class, 'statistiche'])->name('admin.statistiche');
    Route::get('/commesse', [DashboardAdminController::class, 'listaCommesse'])->name('admin.commesse');
    Route::get('/commessa/{commessa}/report', [DashboardAdminController::class, 'reportCommessa'])->name('admin.reportCommessa');
    Route::get('/report-produzione', [DashboardAdminController::class, 'reportProduzione'])->name('admin.reportProduzione');
    Route::get('/cruscotto', [DashboardAdminController::class, 'cruscotto'])->name('admin.cruscotto');
    Route::get('/report-direzione', [DashboardAdminController::class, 'reportDirezione'])->name('admin.reportDirezione');
    Route::get('/report-direzione/excel', [DashboardAdminController::class, 'reportDirezioneExcel'])->name('admin.reportDirezioneExcel');
    Route::get('/report-prinect', [DashboardAdminController::class, 'reportPrinect'])->name('admin.reportPrinect');
    Route::get('/report-prinect/excel', [DashboardAdminController::class, 'reportPrinectExcel'])->name('admin.reportPrinectExcel');

    // Costi & Margini
    Route::get('/costi/tariffe', [CostiMarginiController::class, 'configTariffe'])->name('admin.costi.tariffe');
    Route::post('/costi/tariffe', [CostiMarginiController::class, 'salvaTariffa'])->name('admin.costi.salvaTariffa');
    Route::delete('/costi/tariffe/{id}', [CostiMarginiController::class, 'eliminaTariffa'])->name('admin.costi.eliminaTariffa');
    Route::get('/costi/report', [CostiMarginiController::class, 'reportCosti'])->name('admin.costi.report');
    Route::get('/costi/report/excel', [CostiMarginiController::class, 'reportCostiExcel'])->name('admin.costi.reportExcel');
});

// Prinect — accessibile a owner e admin
Route::middleware(['owner.or.admin'])->prefix('mes/prinect')->group(function() {
    Route::get('/', [PrinectController::class, 'index'])->name('mes.prinect');
    Route::get('/api/status', [PrinectController::class, 'apiStatus'])->name('mes.prinect.apiStatus');
    Route::get('/attivita', [PrinectController::class, 'attivita'])->name('mes.prinect.attivita');
    Route::get('/report/{commessa}', [PrinectController::class, 'reportCommessa'])->name('mes.prinect.report');
    Route::get('/jobs', [PrinectController::class, 'jobs'])->name('mes.prinect.jobs');
    Route::get('/job/{jobId}', [PrinectController::class, 'jobDetail'])->name('mes.prinect.jobDetail');
});

// Produzione
Route::prefix('produzione')->middleware(['operatore.auth'])->group(function() {
    Route::get('/', [ProduzioneController::class, 'index']);
    Route::post('/avvia', [ProduzioneController::class, 'avviaFase'])->name('produzione.avvia');
    Route::post('/aggiungi', [ProduzioneController::class, 'aggiungiProduzione'])->name('produzione.aggiungi');
    Route::post('/termina', [ProduzioneController::class, 'terminaFase'])->name('produzione.termina');
    Route::post('/pausa', [ProduzioneController::class, 'pausaFase'])->name('produzione.pausa');
    Route::post('/aggiorna-campo', [ProduzioneController::class, 'aggiornaCampo'])->name('produzione.aggiornaCampo');
    Route::post('/aggiorna-ordine-campo', [ProduzioneController::class, 'aggiornaOrdineCampo'])->name('produzione.aggiornaOrdineCampo');
});


// Spedizione
Route::prefix('spedizione')->middleware(['operatore.auth'])->group(function() {
    Route::get('/dashboard', [DashboardSpedizioneController::class, 'index'])->name('spedizione.dashboard');
    Route::post('/invio', [DashboardSpedizioneController::class, 'invioAutomatico'])->name('spedizione.invio');
});

// Health check
Route::get('/health', fn() => 'MES OK');
Route::get('/commesse/{commessa}', [App\Http\Controllers\CommessaController::class, 'show'])
->middleware('operatore.auth')
    ->name('commesse.show');


// Homepage
Route::get('/', fn() => view('welcome'));
