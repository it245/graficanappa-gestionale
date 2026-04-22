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
use App\Http\Controllers\FieryController;
use App\Http\Controllers\EtichettaController;
use App\Http\Controllers\PresenzeController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\DdtPdfController;
use App\Http\Controllers\MagazzinoController;
use App\Http\Controllers\MagazzinoMovimentoController;
use App\Http\Controllers\MagazzinoOcrController;
use App\Http\Controllers\MagazzinoEtichettaController;
use App\Http\Controllers\MagazzinoScannerController;

// Operatori
Route::prefix('operatore')->group(function() {
     Route::get('/login', [OperatoreLoginController::class, 'form'])->name('operatore.login');
    Route::post('/login', [OperatoreLoginController::class, 'login'])->middleware('throttle:login')->name('operatore.login.post');

    Route::middleware(['operatore.auth'])->group(function() {
        Route::get('dashboard', [DashboardOperatoreController::class, 'index'])->name('operatore.dashboard');
        Route::match(['get', 'post'], '/logout', [OperatoreLoginController::class, 'logout'])->name('operatore.logout');

        // Fustelle (per operatori fustella piana/cilindrica)
        Route::get('/fustelle', [DashboardOperatoreController::class, 'fustelle'])->name('operatore.fustelle');

        // Prestampa
        Route::get('/prestampa', [DashboardOperatoreController::class, 'prestampa'])->name('operatore.prestampa');
        Route::get('/prestampa/{commessa}', [DashboardOperatoreController::class, 'prestampaDettaglio'])->name('operatore.prestampa.dettaglio');
        Route::post('/prestampa/aggiorna-campo', [DashboardOperatoreController::class, 'prestampaAggiornaCampo'])->name('operatore.prestampa.aggiornaCampo');
        Route::post('/prestampa/sync-onda', [DashboardOperatoreController::class, 'prestampaSyncOnda'])->name('operatore.prestampa.syncOnda');

        // Note turno
        Route::post('/note-turno', [DashboardOperatoreController::class, 'salvaNota'])->name('operatore.salvaNota');
        Route::post('/note-turno/{id}/letta', [DashboardOperatoreController::class, 'segnaLetta'])->name('operatore.segnaLetta');

        // Etichette EAN
        Route::get('/etichetta/search-ean', [EtichettaController::class, 'searchEan'])->name('operatore.etichetta.searchEan');
        Route::post('/etichetta/salva-ean', [EtichettaController::class, 'salvaEan'])->name('operatore.etichetta.salvaEan');
        Route::get('/etichetta/{ordine}', [EtichettaController::class, 'show'])->name('operatore.etichetta');
    });
});

// Owner
Route::middleware(['owner'])->group(function() {
    Route::get('/owner/dashboard', [DashboardOwnerController::class, 'index'])->name('owner.dashboard');
Route::post('/owner/aggiorna-campo', [DashboardOwnerController::class, 'aggiornaCampo'])->name('owner.aggiornaCampo');
Route::post('/owner/import', [DashboardOwnerController::class, 'importOrdini'])->name('owner.importOrdini');
Route::post('/owner/sync-onda', [DashboardOwnerController::class, 'syncOnda'])->name('owner.syncOnda');
Route::post('/owner/aggiungi-riga', [DashboardOwnerController::class, 'aggiungiRiga'])->name('owner.aggiungiRiga');
Route::get('owner/fasi-terminate',[DashboardOwnerController::class, 'fasiTerminate'])->name('owner.fasiTerminate');
Route::get('/owner/commessa/{commessa}', [DashboardOwnerController::class, 'dettaglioCommessa'])->name('owner.dettaglioCommessa');
Route::post('/owner/aggiorna-stato', [DashboardOwnerController::class, 'aggiornaStato'])->name('owner.aggiornaStato');
Route::get('/owner/cerca-commessa', [DashboardOwnerController::class, 'cercaCommessa'])->name('owner.cercaCommessa');
Route::post('/owner/ricalcola-stati', [DashboardOwnerController::class, 'ricalcolaStati'])->name('owner.ricalcolaStati');
Route::post('/owner/elimina-fase', [DashboardOwnerController::class, 'eliminaFase'])->name('owner.eliminaFase');
Route::get('/owner/scheduling', [DashboardOwnerController::class, 'scheduling'])->name('owner.scheduling');
Route::get('/owner/scheduling/excel', [DashboardOwnerController::class, 'schedulingExcel'])->name('owner.schedulingExcel');
Route::get('/owner/bolla/{faseId}', [DashboardOwnerController::class, 'bollaLavorazione'])->whereNumber('faseId')->name('owner.bolla');
Route::get('/owner/scheda/{commessa}', [DashboardOwnerController::class, 'schedaProduzione'])->name('owner.schedaProduzione');
Route::get('/owner/report-ore', [DashboardOwnerController::class, 'reportOre'])->name('owner.reportOre');
Route::get('/owner/excel-download', [DashboardOwnerController::class, 'downloadExcel'])->name('owner.downloadExcel');
Route::get('/owner/esterne', [DashboardOwnerController::class, 'esterne'])->name('owner.esterne');
Route::get('/owner/reparti', [DashboardOwnerController::class, 'repartiOverview'])->name('owner.repartiOverview');
Route::get('/owner/fustelle', [DashboardOwnerController::class, 'fustelleOverview'])->name('owner.fustelle');
Route::post('/owner/tracking-ddt', [DashboardSpedizioneController::class, 'trackingByDDT'])->name('owner.trackingByDDT');
Route::get('/owner/presenti', [PresenzeController::class, 'presenti'])->name('owner.presenti');
Route::get('/owner/presenze', [PresenzeController::class, 'index'])->name('owner.presenze');
Route::get('/owner/note-spedizione', [DashboardSpedizioneController::class, 'noteGiornaliere'])->name('owner.noteSpedizione');
Route::post('/owner/note-spedizione', [DashboardSpedizioneController::class, 'salvaNotaGiornaliera'])->name('owner.salvaNotaSpedizione');
Route::get('/owner/note-spedizione-check', [DashboardSpedizioneController::class, 'noteUltimoAggiornamento'])->name('owner.noteSpedizioneCheck');
Route::get('/owner/audit-log', [DashboardOwnerController::class, 'auditLog'])->name('owner.auditLog');
Route::post('/owner/cliche/set', [DashboardOwnerController::class, 'setCliche'])->name('owner.setCliche');
Route::post('/owner/cliche/clear', [DashboardOwnerController::class, 'clearCliche'])->name('owner.clearCliche');
Route::get('/owner/report-cliche', [DashboardOwnerController::class, 'reportCliche'])->name('owner.reportCliche');
Route::post('/owner/applica-priorita-commessa', [DashboardOwnerController::class, 'applicaPrioritaCommessa'])->name('owner.applicaPrioritaCommessa');
});

// Alert ritardi (protetto con middleware owner)
Route::get('/owner/alert-ritardi', [PresenzeController::class, 'alertRitardi'])->middleware(['owner'])->name('owner.alertRitardi');

// Admin — login pubblico
Route::get('/admin/login', [AdminLoginController::class, 'form'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->middleware('throttle:login')->name('admin.login.post');

// Admin — 2FA challenge (dopo login, prima di admin middleware)
Route::get('/admin/2fa/challenge', [TwoFactorController::class, 'challenge'])->name('admin.2fa.challenge');
Route::post('/admin/2fa/verify', [TwoFactorController::class, 'verify'])->name('admin.2fa.verify');

// Admin — area protetta
Route::middleware(['admin'])->prefix('admin')->group(function() {
    // 2FA setup e gestione
    Route::get('/2fa/setup', [TwoFactorController::class, 'setup'])->name('admin.2fa.setup');
    Route::post('/2fa/confirm', [TwoFactorController::class, 'confirm'])->name('admin.2fa.confirm');
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('admin.2fa.disable');
    Route::get('/2fa/devices', [TwoFactorController::class, 'devices'])->name('admin.2fa.devices');
    Route::delete('/2fa/devices/{id}', [TwoFactorController::class, 'revokeDevice'])->name('admin.2fa.revokeDevice');
    Route::match(['get', 'post'], '/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');
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
    Route::get('/turni', [DashboardAdminController::class, 'turni'])->name('admin.turni');
    Route::post('/turni/salva', [DashboardAdminController::class, 'salvaTurno'])->name('admin.salvaTurno');
    Route::get('/report-direzione', [DashboardAdminController::class, 'reportDirezione'])->name('admin.reportDirezione');
    Route::get('/report-direzione/excel', [DashboardAdminController::class, 'reportDirezioneExcel'])->name('admin.reportDirezioneExcel');
    Route::get('/report-prinect', [DashboardAdminController::class, 'reportPrinect'])->name('admin.reportPrinect');
    Route::get('/report-prinect/excel', [DashboardAdminController::class, 'reportPrinectExcel'])->name('admin.reportPrinectExcel');

    // Report operatori-reparti-fasi
    Route::get('/report-operatori', [DashboardAdminController::class, 'reportOperatori'])->name('admin.reportOperatori');

    // EAN Prodotti
    Route::post('/ean', [DashboardAdminController::class, 'salvaEan'])->name('admin.ean.salva');
    Route::post('/ean/{id}', [DashboardAdminController::class, 'aggiornaEan'])->name('admin.ean.aggiorna');
    Route::delete('/ean/{id}', [DashboardAdminController::class, 'eliminaEan'])->name('admin.ean.elimina');

    // Costi & Margini
    Route::get('/costi/tariffe', [CostiMarginiController::class, 'configTariffe'])->name('admin.costi.tariffe');
    Route::post('/costi/tariffe', [CostiMarginiController::class, 'salvaTariffa'])->name('admin.costi.salvaTariffa');
    Route::delete('/costi/tariffe/{id}', [CostiMarginiController::class, 'eliminaTariffa'])->name('admin.costi.eliminaTariffa');
    Route::get('/costi/report', [CostiMarginiController::class, 'reportCosti'])->name('admin.costi.report');
    Route::get('/costi/report/excel', [CostiMarginiController::class, 'reportCostiExcel'])->name('admin.costi.reportExcel');

    // Audit Log
    Route::get('/audit-log', [DashboardAdminController::class, 'auditLog'])->name('admin.auditLog');
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

// Fiery V900 — dashboard e debug solo owner/admin
Route::middleware(['owner.or.admin'])->prefix('mes/fiery')->group(function() {
    Route::get('/', [FieryController::class, 'index'])->name('mes.fiery');
    Route::get('/status', [FieryController::class, 'statusJson'])->name('mes.fiery.status');
    Route::get('/debug', [FieryController::class, 'debugSync'])->name('mes.fiery.debug');
});

// Fiery contatori — accessibile anche al ruolo fiery_contatori
Route::middleware(['owner.or.admin:fiery_contatori'])->prefix('mes/fiery')->group(function() {
    Route::get('/contatori', [FieryController::class, 'contatori'])->name('mes.fiery.contatori');
    Route::get('/contatori/json', [FieryController::class, 'contatoriJson'])->name('mes.fiery.contatori.json');
    Route::get('/contatori/report-categorie', [FieryController::class, 'reportCategoriePdf'])->name('mes.fiery.contatori.reportCategorie');
});

// Produzione
Route::prefix('produzione')->middleware(['operatore.auth'])->group(function() {
    Route::get('/', [ProduzioneController::class, 'index']);
    Route::post('/avvia', [ProduzioneController::class, 'avviaFase'])->name('produzione.avvia');
    Route::post('/aggiungi', [ProduzioneController::class, 'aggiungiProduzione'])->name('produzione.aggiungi');
    Route::post('/termina', [ProduzioneController::class, 'terminaFase'])->name('produzione.termina');
    Route::post('/pausa', [ProduzioneController::class, 'pausaFase'])->name('produzione.pausa');
    Route::post('/riprendi', [ProduzioneController::class, 'riprendiFase'])->name('produzione.riprendi');
    Route::post('/aggiorna-campo', [ProduzioneController::class, 'aggiornaCampo'])->name('produzione.aggiornaCampo');
    Route::post('/aggiorna-ordine-campo', [ProduzioneController::class, 'aggiornaOrdineCampo'])->name('produzione.aggiornaOrdineCampo');
});


// Spedizione
Route::prefix('spedizione')->middleware(['operatore.auth'])->group(function() {
    Route::get('/dashboard', [DashboardSpedizioneController::class, 'index'])->name('spedizione.dashboard');
    Route::get('/esterne', [DashboardSpedizioneController::class, 'esterne'])->name('spedizione.esterne');
    Route::post('/invio', [DashboardSpedizioneController::class, 'invioAutomatico'])->name('spedizione.invio');
    Route::post('/recupera', [DashboardSpedizioneController::class, 'recuperaConsegna'])->name('spedizione.recupera');
    Route::post('/tracking', [DashboardSpedizioneController::class, 'tracking'])->name('spedizione.tracking');
    Route::post('/tracking-ddt', [DashboardSpedizioneController::class, 'trackingByDDT'])->name('spedizione.trackingByDDT');
    Route::get('/note-giornaliere', [DashboardSpedizioneController::class, 'noteGiornaliere'])->name('spedizione.noteGiornaliere');
    Route::post('/note-giornaliere', [DashboardSpedizioneController::class, 'salvaNotaGiornaliera'])->name('spedizione.salvaNotaGiornaliera');
    Route::get('/note-check', [DashboardSpedizioneController::class, 'noteUltimoAggiornamento'])->name('spedizione.noteCheck');
    Route::get('/notifiche', [DashboardSpedizioneController::class, 'notifiche'])->name('spedizione.notifiche');
    Route::post('/notifiche/{id}/letta', [DashboardSpedizioneController::class, 'notificaLetta'])->name('spedizione.notificaLetta');
    Route::post('/notifiche/lette', [DashboardSpedizioneController::class, 'notificheLette'])->name('spedizione.notificheLette');
    Route::post('/sync-onda', [DashboardSpedizioneController::class, 'syncOnda'])->name('spedizione.syncOnda');
    Route::get('/ricerca-ddt', [DashboardSpedizioneController::class, 'ricercaDdt'])->name('spedizione.ricercaDdt');
});

// Tracking BRT (protetto)
Route::get('/spedizione/tracking-test', [DashboardSpedizioneController::class, 'trackingTest'])->middleware(['operatore.auth'])->name('spedizione.trackingTest');
Route::get('/spedizione/tracking-json/{segnacollo}', [DashboardSpedizioneController::class, 'trackingJson'])->middleware(['operatore.auth'])->name('spedizione.trackingJson');

// Prototipo nuova UI
Route::get('/proto/owner', [DashboardOwnerController::class, 'prototipo'])->name('proto.owner');

// Etichette — lista commesse (accesso libero)
Route::get('/etichette', [EtichettaController::class, 'lista'])->name('etichette.lista');

// Chat
Route::middleware('operatore.auth')->prefix('chat')->group(function () {
    Route::get('/', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/invia', [ChatController::class, 'invia'])->name('chat.invia');
    Route::get('/messaggi', [ChatController::class, 'messaggi'])->name('chat.messaggi');
});

// Magazzino Carta — accessibile da spedizione (Emanuele), owner e admin
Route::middleware(['magazzino.auth'])->prefix('magazzino')->group(function () {
    Route::get('/', [MagazzinoController::class, 'dashboard'])->name('magazzino.dashboard');
    Route::get('/articoli', [MagazzinoController::class, 'articoli'])->name('magazzino.articoli');
    Route::post('/articoli', [MagazzinoController::class, 'storeArticolo'])->name('magazzino.articoli.store');
    Route::get('/giacenze', [MagazzinoController::class, 'giacenze'])->name('magazzino.giacenze');
    Route::get('/movimenti', [MagazzinoController::class, 'movimenti'])->name('magazzino.movimenti');

    // Carico da bolla
    Route::get('/carico', [MagazzinoMovimentoController::class, 'formCarico'])->name('magazzino.carico');
    Route::post('/carico/ocr', [MagazzinoOcrController::class, 'processaBolla'])->name('magazzino.ocr');
    Route::post('/carico', [MagazzinoMovimentoController::class, 'registraCarico'])->name('magazzino.carico.store');

    // Scarico/Prelievo
    Route::get('/prelievo', [MagazzinoMovimentoController::class, 'formPrelievo'])->name('magazzino.prelievo');
    Route::post('/prelievo', [MagazzinoMovimentoController::class, 'registraPrelievo'])->name('magazzino.prelievo.store');

    // Reso
    Route::get('/reso', [MagazzinoMovimentoController::class, 'formReso'])->name('magazzino.reso');
    Route::post('/reso', [MagazzinoMovimentoController::class, 'registraReso'])->name('magazzino.reso.store');

    // Rettifica
    Route::get('/rettifica', [MagazzinoMovimentoController::class, 'formRettifica'])->name('magazzino.rettifica');
    Route::post('/rettifica', [MagazzinoMovimentoController::class, 'registraRettifica'])->name('magazzino.rettifica.store');

    // Scanner QR
    Route::get('/scan', [MagazzinoScannerController::class, 'scanner'])->name('magazzino.scanner');
    Route::post('/scan/lookup', [MagazzinoScannerController::class, 'lookup'])->name('magazzino.scan.lookup');

    // Etichette
    Route::get('/etichetta/{id}', [MagazzinoEtichettaController::class, 'stampa'])->name('magazzino.etichetta.stampa');

    // Alert
    Route::get('/alert', [MagazzinoController::class, 'alertSoglia'])->name('magazzino.alert');

    // Fabbisogno materiali
    Route::get('/fabbisogno', [MagazzinoController::class, 'fabbisogno'])->name('magazzino.fabbisogno');
    Route::get('/fabbisogno/check/{commessa}', function ($commessa) {
        return response()->json(\App\Services\FabbisognoService::verificaDisponibilita($commessa));
    })->name('magazzino.fabbisogno.check');

    // Ordini di acquisto
    Route::get('/ordini-acquisto', [MagazzinoController::class, 'ordiniAcquisto'])->name('magazzino.ordiniAcquisto');
});

// Operatore: prelievo dalla propria dashboard (scansione QR)
Route::middleware(['operatore.auth'])->group(function () {
    Route::get('/operatore/preleva-carta', [MagazzinoScannerController::class, 'scannerOperatore'])->name('operatore.prelevaCarta');
    Route::post('/operatore/preleva-carta', [MagazzinoScannerController::class, 'prelievoOperatore'])->name('operatore.prelevaCarta.store');
});

// CSRF token refresh (mantiene sessione viva — richiede auth)
Route::get('/csrf-refresh', fn() => response()->json(['token' => csrf_token()]))->middleware('operatore.auth');

// Prinect API Explorer (protetto)
Route::get('/prinect-explorer', fn() => view('prinect_explorer'))->middleware('owner.or.admin');

// Health check
// Push notifications
Route::post('/push/subscribe', [PushController::class, 'subscribe'])->name('push.subscribe');
Route::post('/push/unsubscribe', [PushController::class, 'unsubscribe'])->name('push.unsubscribe');
Route::get('/push/vapid-key', [PushController::class, 'vapidKey'])->name('push.vapidKey');

Route::get('/health', fn() => 'MES OK');
Route::get('/commesse/{commessa}', [App\Http\Controllers\CommessaController::class, 'show'])
->middleware('operatore.auth')
    ->name('commesse.show');


// TV Kiosk (no auth) — dati reali
Route::get('/kiosk', [\App\Http\Controllers\KioskController::class, 'index']);
Route::post('/kiosk/nota', [\App\Http\Controllers\KioskController::class, 'salvaNota'])->middleware(['owner'])->name('kiosk.salvaNota');
Route::get('/kiosk/nota', [\App\Http\Controllers\KioskController::class, 'getNota'])->name('kiosk.getNota');

// Kiosk demo (dati finti per test locale)
Route::get('/kiosk-demo', function() {
    return view('kiosk', [
        'kpi' => ['completate' => 19, 'in_corso' => 8, 'in_coda' => 23, 'fustelle' => 73],
        'macchine' => [
            ['nome' => 'XL 106', 'attiva' => true, 'commessa' => '0066853-26', 'cliente' => 'ITALIANA CONFETTI', 'descrizione' => 'Astuccio Allegra 30gr FS0045', 'ore_lav' => 3.2, 'ore_prev' => 5.0],
            ['nome' => 'BOBST', 'attiva' => true, 'commessa' => '0066660-26', 'cliente' => 'ITALIANA CONFETTI', 'descrizione' => 'Ast. 1kg Maxtris Mix Delice', 'ore_lav' => 1.8, 'ore_prev' => 4.5],
            ['nome' => 'JOH Caldo', 'attiva' => true, 'commessa' => '0066821-26', 'cliente' => 'ITALIANA CONFETTI', 'descrizione' => 'AST 1 KG Castelli', 'ore_lav' => 2.4, 'ore_prev' => 3.0],
            ['nome' => 'Plastificatrice', 'attiva' => true, 'commessa' => '0066844-26', 'cliente' => 'MAGLUXURY SRL', 'descrizione' => 'Scheda porta fiale Riviera', 'ore_lav' => 0.5, 'ore_prev' => 1.5],
            ['nome' => 'Piegaincolla', 'attiva' => true, 'commessa' => '0066616-26', 'cliente' => 'ITALIANA CONFETTI', 'descrizione' => 'Ast. 500gr Praline Assortite', 'ore_lav' => 1.5, 'ore_prev' => 2.7],
            ['nome' => 'Finestratrice', 'attiva' => true, 'commessa' => '0066686-26', 'cliente' => 'ITALIANA CONFETTI', 'descrizione' => 'Ast. 1kg Maxtris Classici', 'ore_lav' => 0.6, 'ore_prev' => 3.0],
            ['nome' => 'STEL', 'attiva' => true, 'commessa' => '0066873-26', 'cliente' => 'TIFATA PLASTICA', 'descrizione' => 'ETI CIL. Casalplastik', 'ore_lav' => 1.1, 'ore_prev' => 1.6],
            ['nome' => 'HP Indigo', 'attiva' => false, 'commessa' => '', 'cliente' => '', 'descrizione' => '', 'ore_lav' => 0, 'ore_prev' => 0],
        ],
        'prossimi' => [
            'XL 106' => [
                ['desc' => 'ETI N 161 Mako Quarzoplast', 'badge' => 'NO CALDO', 'badge_cls' => 'arancio'],
                ['desc' => 'ETI N 171 Lavernova Midi', 'badge' => 'NO CALDO', 'badge_cls' => 'arancio'],
                ['desc' => 'Scheda porta fiale Polignano', 'badge' => 'NO RILIEVI', 'badge_cls' => 'rosso'],
            ],
            'BOBST' => [
                ['desc' => 'Ast. 500gr Sfumati Rosa FS0898', 'badge' => 'FS0898', 'badge_cls' => 'verde'],
                ['desc' => 'Ast. 1kg Yogurt Frutti FS0898', 'badge' => 'FS0898', 'badge_cls' => 'verde'],
                ['desc' => 'Ast. Celebrate Napolitains FS2053', 'badge' => 'FS2053', 'badge_cls' => 'verde'],
            ],
            'JOH CALDO' => [
                ['desc' => 'Ast. Les Noisettes 500gr', 'badge' => 'FS0044', 'badge_cls' => 'verde'],
                ['desc' => 'Ast. 1kg Enzo Miccio Lilla', 'badge' => 'FS0898', 'badge_cls' => 'verde'],
            ],
            'PIEGAINCOLLA' => [
                ['desc' => 'Ast. 1kg Mix Delice FS0898', 'badge' => 'PI01', 'badge_cls' => 'blu'],
                ['desc' => 'Ast. Vassoio Confetti FS0157', 'badge' => 'PI01', 'badge_cls' => 'blu'],
                ['desc' => 'Libro cartonato Connie n.2', 'badge' => 'NO CALDO', 'badge_cls' => 'arancio'],
            ],
        ],
        'obiettivo' => ['completate' => 19, 'target' => 20, 'pct' => 95, 'ultima_ora' => 3, 'ore' => 45.2],
        'utilizzo' => [
            ['nome' => 'XL 106 (24h)', 'pct' => 45],
            ['nome' => 'BOBST', 'pct' => 62],
            ['nome' => 'JOH Caldo', 'pct' => 55],
            ['nome' => 'Plastificatrice', 'pct' => 38],
            ['nome' => 'Piegaincolla', 'pct' => 30],
            ['nome' => 'Finestratrice', 'pct' => 48],
            ['nome' => 'STEL', 'pct' => 25],
            ['nome' => 'HP Indigo', 'pct' => 72],
            ['nome' => 'Tagliacarte', 'pct' => 12],
            ['nome' => 'Legatoria', 'pct' => 35],
        ],
        'solar' => (new \App\Services\SolarLogService())->getDati(),
    ]);
});

// Report commesse per percorso produttivo (Excel)
Route::get('/report-percorso/excel', function () {
    return \Maatwebsite\Excel\Facades\Excel::download(
        new \App\Exports\ReportPercorsoExport(),
        'Commesse_Percorso_' . now()->format('Y-m-d') . '.xlsx'
    );
});

// Report commesse per percorso produttivo (HTML)
Route::get('/report-percorso', function () {
    $ordini = \App\Models\Ordine::whereHas('fasi', fn($q) => $q->where('stato', '<', 4))
        ->with(['fasi.faseCatalogo'])
        ->orderBy('commessa')
        ->get();

    $gruppi = [
        'percorso-base'     => ['label' => 'BASE — No Caldo, No Rilievi',     'color' => '#d4edda', 'border' => '#28a745', 'ordini' => []],
        'percorso-rilievi'  => ['label' => 'RILIEVI — Solo Rilievi',          'color' => '#fff3cd', 'border' => '#ffc107', 'ordini' => []],
        'percorso-caldo'    => ['label' => 'CALDO — Solo Stampa a Caldo',     'color' => '#f96f2a', 'border' => '#e65c00', 'ordini' => []],
        'percorso-completo' => ['label' => 'COMPLETO — Caldo + Rilievi',      'color' => '#f8d7da', 'border' => '#dc3545', 'ordini' => []],
    ];

    foreach ($ordini as $ordine) {
        $classe = $ordine->getPercorsoClass();
        if (!isset($gruppi[$classe])) continue;
        $fasiTot = $ordine->fasi->count();
        $fasiComplete = $ordine->fasi->where('stato', '>=', 3)->count();
        $gruppi[$classe]['ordini'][] = [
            'commessa'    => $ordine->commessa,
            'cliente'     => $ordine->cliente_nome ?? '-',
            'cod_art'     => $ordine->cod_art ?? '-',
            'descrizione' => $ordine->descrizione ?? '-',
            'qta'         => number_format($ordine->qta_richiesta ?? 0, 0, ',', '.'),
            'consegna'    => $ordine->data_prevista_consegna ? \Carbon\Carbon::parse($ordine->data_prevista_consegna)->format('d/m/Y') : '-',
            'progresso'   => "$fasiComplete/$fasiTot",
            'fasi'        => $ordine->fasi->map(fn($f) => $f->faseCatalogo->nome ?? $f->fase ?? '-')->implode(', '),
        ];
    }

    return view('report.percorso', ['gruppi' => $gruppi, 'totale' => $ordini->count()]);
});

// DDT PDF
Route::get('/ddt/pdf/{numeroDdt}', [DdtPdfController::class, 'genera'])->name('ddt.pdf');

// Homepage
Route::get('/', fn() => view('welcome'));
