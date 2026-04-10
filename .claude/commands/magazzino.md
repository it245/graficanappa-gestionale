# Magazzino Carta — MES Grafica Nappa v2.0

Sistema di gestione magazzino carta con etichette QR e OCR Tesseract per tracciabilità completa dalla ricezione bolla alla spedizione.

## CONTESTO

- **Azienda**: Grafica Nappa srl — Tipografia industriale, Aversa (CE)
- **Branch**: def2.0
- **Layout**: layouts.mes (sidebar+topbar, dark mode, enterprise UI)
- **Stack**: Laravel 12, PHP 8.5, MySQL, Bootstrap 5, Inter font
- **PWA**: già installabile con service worker
- **Etichette**: già sistema etichette con stampante (DataMatrix, EAN)
- **Cod carta**: già presente in ordine_fasi come `cod_carta` (es. 02W.SE.PW.300.0007)
- **Addetto magazzino**: Emanuele Marcone (ruolo spedizione, NON nuovo ruolo)
- **Regola scarico**: la carta si scarica UNA SOLA VOLTA alla fase STAMPA. Le fasi successive (plastifica, fustella, piega) lavorano sullo stesso foglio.

## FLUSSO OPERATIVO

### 1. ARRIVO MERCE (Bolla fornitore)
- Emanuele apre MES → pagina "Magazzino" → bottone "Registra Bolla"
- Scatta foto alla bolla cartacea con fotocamera telefono/tablet
- Tesseract OCR legge la foto ed estrae: fornitore, tipo carta, quantità, lotto, formato, grammatura
- Emanuele conferma/corregge i dati estratti in un form
- Sistema salva il movimento di CARICO
- Sistema stampa automaticamente etichetta QR da attaccare al bancale
- Etichetta contiene: QR code + testo leggibile (cod carta, tipo, formato, grammatura, qta, lotto, ubicazione)

### 2. PRELIEVO PER PRODUZIONE
- Operatore deve stampare una commessa
- Dalla SUA dashboard (già loggato) → bottone "Preleva carta" o scansiona QR bancale
- Scansiona QR del bancale con fotocamera telefono
- Il sistema riconosce il bancale e mostra: tipo carta, giacenza disponibile
- Operatore seleziona commessa (o il sistema propone la commessa attiva)
- Operatore conferma quantità prelevata
- Sistema scala la giacenza e registra il movimento di SCARICO associato alla commessa
- IMPORTANTE: lo scarico avviene SOLO per fase STAMPA (offset o digitale), NON per fasi successive

### 3. RESO/RIENTRO
- Fogli avanzati dopo la stampa tornano in magazzino
- Emanuele scansiona QR bancale → registra quantità rientrata
- Movimento di RESO registrato

### 4. INVENTARIO
- Pagina inventario con lista giacenze per tipo carta
- Scansione rapida QR per verifica
- Alert automatico sotto soglia minima
- Differenze inventariali segnalate

## ARCHITETTURA DATABASE

### Migration 1: `magazzino_articoli` (anagrafica tipi carta)
```php
Schema::create('magazzino_articoli', function (Blueprint $table) {
    $table->id();
    $table->string('codice')->unique();          // cod_carta (es. 02W.SE.PW.300.0007)
    $table->string('descrizione');                // GC1 PERFORMA WHITE 56x102 300g
    $table->string('tipo_carta')->nullable();     // GC1, GC2, patinata, polipropilene
    $table->string('formato')->nullable();        // 56x102, 70x82, 72x102
    $table->integer('grammatura')->nullable();     // 300, 400, 190
    $table->decimal('spessore', 5, 3)->nullable(); // 0.475
    $table->string('um', 10)->default('fg');       // fg, mq, kg
    $table->integer('soglia_minima')->default(0);  // alert sotto questa qta
    $table->string('fornitore')->nullable();
    $table->string('certificazioni')->nullable();  // FSC, alimentare, ecc
    $table->boolean('attivo')->default(true);
    $table->timestamps();
});
```

### Migration 2: `magazzino_ubicazioni` (posizioni fisiche)
```php
Schema::create('magazzino_ubicazioni', function (Blueprint $table) {
    $table->id();
    $table->string('codice')->unique();   // A3-02, B1-05
    $table->string('corridoio');          // A, B, C
    $table->string('scaffale');           // 1, 2, 3
    $table->string('piano')->nullable();  // 01, 02
    $table->string('note')->nullable();
    $table->timestamps();
});
```

### Migration 3: `magazzino_movimenti` (carico/scarico/reso)
```php
Schema::create('magazzino_movimenti', function (Blueprint $table) {
    $table->id();
    $table->foreignId('articolo_id')->constrained('magazzino_articoli');
    $table->foreignId('ubicazione_id')->nullable()->constrained('magazzino_ubicazioni');
    $table->enum('tipo', ['carico', 'scarico', 'reso', 'rettifica']);
    $table->integer('quantita');               // positivo per carico/reso, negativo per scarico
    $table->integer('giacenza_dopo');          // snapshot giacenza dopo il movimento
    $table->string('lotto')->nullable();
    $table->string('fornitore')->nullable();   // solo per carico
    $table->string('commessa')->nullable();    // solo per scarico (num commessa Onda)
    $table->string('fase')->nullable();        // solo per scarico (es. STAMPAXL106)
    $table->foreignId('operatore_id')->nullable()->constrained('operatori');
    $table->string('note')->nullable();
    $table->string('foto_bolla')->nullable();  // path foto bolla (solo carico)
    $table->text('ocr_raw')->nullable();       // testo grezzo OCR (solo carico)
    $table->timestamps();
});
```

### Migration 4: `magazzino_giacenze` (giacenza corrente per articolo+ubicazione)
```php
Schema::create('magazzino_giacenze', function (Blueprint $table) {
    $table->id();
    $table->foreignId('articolo_id')->constrained('magazzino_articoli');
    $table->foreignId('ubicazione_id')->nullable()->constrained('magazzino_ubicazioni');
    $table->integer('quantita')->default(0);
    $table->string('lotto')->nullable();
    $table->date('data_ultimo_carico')->nullable();
    $table->date('data_ultimo_scarico')->nullable();
    $table->unique(['articolo_id', 'ubicazione_id', 'lotto']);
    $table->timestamps();
});
```

### Migration 5: `magazzino_etichette` (etichette QR stampate)
```php
Schema::create('magazzino_etichette', function (Blueprint $table) {
    $table->id();
    $table->string('qr_code')->unique();       // UUID o codice univoco
    $table->foreignId('articolo_id')->constrained('magazzino_articoli');
    $table->foreignId('ubicazione_id')->nullable()->constrained('magazzino_ubicazioni');
    $table->foreignId('giacenza_id')->nullable()->constrained('magazzino_giacenze');
    $table->string('lotto')->nullable();
    $table->integer('quantita_iniziale');
    $table->boolean('attiva')->default(true);
    $table->timestamps();
});
```

## ARCHITETTURA CODICE

### Model
- `app/Models/MagazzinoArticolo.php`
- `app/Models/MagazzinoUbicazione.php`
- `app/Models/MagazzinoMovimento.php`
- `app/Models/MagazzinoGiacenza.php`
- `app/Models/MagazzinoEtichetta.php`

### Controller
- `app/Http/Controllers/MagazzinoController.php` — dashboard, CRUD articoli, giacenze
- `app/Http/Controllers/MagazzinoMovimentoController.php` — carico, scarico, reso
- `app/Http/Controllers/MagazzinoOcrController.php` — upload foto, OCR Tesseract
- `app/Http/Controllers/MagazzinoEtichettaController.php` — genera e stampa QR
- `app/Http/Controllers/MagazzinoScannerController.php` — scansione QR, prelievo

### Service
- `app/Services/MagazzinoService.php` — logica carico/scarico/giacenza
- `app/Services/OcrBollaService.php` — Tesseract OCR per bolle
- `app/Services/QrEtichettaService.php` — generazione QR code + PDF etichetta

### Routes
```php
// Magazzino: accessibile da spedizione (Emanuele), owner e admin
Route::middleware(['auth', 'role:spedizione|owner|admin'])->prefix('magazzino')->group(function () {
    Route::get('/', [MagazzinoController::class, 'dashboard']);
    Route::get('/articoli', [MagazzinoController::class, 'articoli']);
    Route::post('/articoli', [MagazzinoController::class, 'storeArticolo']);
    Route::get('/giacenze', [MagazzinoController::class, 'giacenze']);
    Route::get('/movimenti', [MagazzinoController::class, 'movimenti']);
    Route::get('/ubicazioni', [MagazzinoController::class, 'ubicazioni']);

    // Carico da bolla
    Route::get('/carico', [MagazzinoMovimentoController::class, 'formCarico']);
    Route::post('/carico/ocr', [MagazzinoOcrController::class, 'processaBolla']);
    Route::post('/carico', [MagazzinoMovimentoController::class, 'registraCarico']);

    // Scarico/Prelievo
    Route::get('/prelievo', [MagazzinoMovimentoController::class, 'formPrelievo']);
    Route::post('/prelievo', [MagazzinoMovimentoController::class, 'registraPrelievo']);

    // Scanner QR
    Route::get('/scan', [MagazzinoScannerController::class, 'scanner']);
    Route::post('/scan/lookup', [MagazzinoScannerController::class, 'lookup']);

    // Etichette
    Route::get('/etichetta/{id}', [MagazzinoEtichettaController::class, 'stampa']);

    // Alert
    Route::get('/alert', [MagazzinoController::class, 'alertSoglia']);
});

// Operatore: prelievo dalla propria dashboard (scansione QR)
Route::middleware(['auth', 'operatore.auth'])->group(function () {
    Route::get('/operatore/preleva-carta', [MagazzinoScannerController::class, 'scannerOperatore']);
    Route::post('/operatore/preleva-carta', [MagazzinoScannerController::class, 'prelievoOperatore']);
});
```

### Viste Blade
- `resources/views/magazzino/dashboard.blade.php` — KPI, giacenze basse, ultimi movimenti
- `resources/views/magazzino/articoli.blade.php` — CRUD anagrafica carta
- `resources/views/magazzino/giacenze.blade.php` — tabella giacenze con filtri
- `resources/views/magazzino/movimenti.blade.php` — storico movimenti con filtri
- `resources/views/magazzino/carico.blade.php` — form registra bolla (con upload foto + OCR)
- `resources/views/magazzino/prelievo.blade.php` — form prelievo
- `resources/views/magazzino/scanner.blade.php` — scanner QR fullscreen (fotocamera)
- `resources/views/magazzino/etichetta.blade.php` — PDF etichetta QR per stampa
- `resources/views/magazzino/ubicazioni.blade.php` — gestione scaffali

### Dipendenze
- `thiagoalessio/tesseract_ocr` — OCR PHP wrapper per Tesseract
- `simplesoftwareio/simple-qrcode` — generazione QR code
- `html5-qrcode` (JS) — scanner QR da fotocamera browser (già usato per etichette)

## OCR TESSERACT — Configurazione

### Installazione su Windows Server (.60)
```
choco install tesseract -y
```
Oppure download da https://github.com/UB-Mannheim/tesseract/wiki — installare con lingua italiana.

### Composer
```
composer require thiagoalessio/tesseract_ocr
```

### OcrBollaService
```php
class OcrBollaService
{
    public static function leggi(string $imagePath): array
    {
        $ocr = new TesseractOCR($imagePath);
        $ocr->lang('ita');
        $testo = $ocr->run();

        return self::parseBolla($testo);
    }

    private static function parseBolla(string $testo): array
    {
        // Regex per estrarre dati comuni dalle bolle:
        // - Fornitore (prima riga o dopo "Spett.le")
        // - Quantità (numero + fg/mq/kg)
        // - Grammatura (numero + g/gr/grammi)
        // - Formato (NNxNN o NN x NN)
        // - Lotto (dopo "Lotto" o "L.")
        // Restituisce array con campi pre-compilati per il form
    }
}
```

## ETICHETTA QR — Layout
```
┌─────────────────────────────┐
│ ┌─────┐  GRAFICA NAPPA      │
│ │ QR  │  MAGAZZINO CARTA     │
│ │CODE │                      │
│ └─────┘  Cod: 02W.SE.PW.300 │
│          GC1 Performa White  │
│          56x102  300g        │
│          Qta: 2.000 fg      │
│          Lotto: L240408      │
│          Ubicazione: A3-02   │
│          Data: 08/04/2026    │
└─────────────────────────────┘
```

Il QR contiene URL: `http://192.168.1.60/magazzino/scan?qr={UUID}`

## DASHBOARD MAGAZZINO — Layout

### KPI (cards in alto)
- Articoli in anagrafica
- Giacenza totale (fogli)
- Movimenti oggi
- Alert sotto soglia (badge rosso)

### Tabella giacenze
- Filtri: tipo carta, formato, grammatura, ubicazione
- Colonne: Codice, Descrizione, Formato, Grammatura, Giacenza, Soglia, Ubicazione, Ultimo carico
- Riga rossa se giacenza < soglia

### Ultimi movimenti
- Tipo (carico/scarico/reso), Articolo, Qta, Commessa, Operatore, Data

### Sidebar
- Dashboard
- Registra Bolla (carico)
- Prelievo
- Articoli
- Ubicazioni
- Movimenti
- Alert
- Scanner QR

## COME USARE

Quando l'utente invoca `/magazzino`:
1. Analizza il codebase per verificare dipendenze e struttura esistente
2. Crea le migration in ordine
3. Crea i model con relazioni
4. Crea i service (Magazzino, OCR, QR)
5. Crea i controller
6. Crea le viste Blade con layout layouts.mes
7. Aggiungi le route
8. Aggiungi la voce "Magazzino" nella sidebar (owner + spedizione)
9. Installa dipendenze composer (tesseract_ocr, simple-qrcode)
10. Testa il flusso completo
11. Committa e pusha su def2.0

## NOTE IMPORTANTI
- Tutto in italiano
- Layout: `@extends('layouts.mes')` con sidebar e topbar
- Stile: Bootstrap 5, Inter font, CSS variables per dark mode
- Scanner QR: usa `html5-qrcode` (libreria JS già nel progetto)
- Lo scarico carta avviene SOLO per fase STAMPA, MAI per fasi successive
- L'operatore è già loggato → sappiamo chi sta scansionando
- Il QR contiene un URL del MES → scansionabile con qualsiasi telefono
- Emanuele Marcone (spedizione) è l'addetto al magazzino — usa il ruolo spedizione esistente
