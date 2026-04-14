# Data Audit — MES Grafica Nappa

Agisci come un Senior Data Analyst specializzato in applicazioni Laravel/MySQL enterprise. L'utente è un analista dati: tutti i dati del MES DEVONO essere nel database, mai calcolati al volo da API esterne o persi al riavvio.

## OBIETTIVO
1. Trovare TUTTI i dati nel MES che NON sono persistiti nel database (calcolati al volo, letti da API esterne, cachati solo in memoria)
2. Analizzare la lentezza generale dell'applicazione e proporre fix concreti
3. Per ogni dato mancante: proporre migration, model, e logica di sync

## FASE 1: AUDIT DATI NON PERSISTITI (lancia agenti in parallelo)

### Agente 1 — API Esterne senza DB
Lancia un agente `Explore` per trovare TUTTI i punti dove il codice chiama API esterne e mostra i dati SENZA salvarli nel DB:
- Fiery API (jobs, accounting, status) — cerca `getJobs`, `getAccountingPerCommessa`, `getServerStatus`, `apiGet`
- Prinect API — cerca `getDeviceActivity`, `getWorksteps`, `PrinectService`
- Onda SOAP — cerca `SoapClient`, `OndaSyncService` (verifica cosa NON viene salvato)
- SNMP — cerca `snmpget`, `leggiContatoriSnmp`
- Qualsiasi `Http::get`, `Http::post`, `curl`, `file_get_contents` verso host esterni
- Dati in `Cache::remember` che dovrebbero essere nel DB (cache è volatile!)

File prioritari:
```
app/Http/Services/FieryService.php
app/Http/Services/FierySyncService.php
app/Http/Services/PrinectService.php
app/Http/Services/PrinectSyncService.php
app/Services/OndaSyncService.php
app/Http/Controllers/FieryController.php
app/Http/Controllers/DashboardOwnerController.php
app/Console/Commands/
```

Per ogni dato trovato, classificare:
- **CRITICO**: dato mostrato all'utente che si perde a stampante/server spento
- **ALTO**: dato calcolato al volo che potrebbe essere storicizzato
- **MEDIO**: dato di cache che sarebbe meglio persistere
- **BASSO**: dato volatile per natura (stato real-time)

### Agente 2 — Calcoli ripetuti senza persistenza
Lancia un agente `Explore` per trovare calcoli costosi fatti ad ogni page load:
- `calcolaOreEPriorita()` — ricalcola ore/priorità ad ogni richiesta
- `config('fasi_ore')` usato in loop per calcoli — risultati non salvati
- Aggregazioni fatte con `->map()`, `->sum()`, `->filter()` su collection grandi
- KPI calcolati al volo (conteggi, somme, medie) che potrebbero essere pre-calcolati
- `Carbon::parse()` ripetuti sugli stessi campi

File prioritari:
```
app/Http/Controllers/DashboardOwnerController.php
app/Http/Controllers/DashboardOperatoreController.php
app/Http/Controllers/DashboardSpedizioneController.php
app/Exports/DashboardMesExport.php
app/Http/Controllers/ReportOreController.php
```

### Agente 3 — Lentezza e Performance
Lancia un agente `Explore` per trovare le cause di lentezza:
- `set_time_limit` > 30 — significa che il codice è lento
- `->get()` senza `->paginate()` su tabelle grandi (ordine_fasi ha 10k+ record)
- N+1 query: `@foreach` che accede a relazioni senza `->with()`
- Chiamate HTTP sincrone nel page load (Fiery, Prinect, SNMP)
- `sleep()` o timeout lunghi
- File I/O nel page load (Excel read/write, file_get_contents)
- Query senza indici (JOIN senza indice, WHERE su colonne non indicizzate)
- Mancanza di paginazione su pagine con molti record

File da controllare:
```
app/Http/Controllers/*.php
app/Http/Services/*.php
app/Services/*.php
app/Console/Commands/*.php
config/fasi_ore.php
config/fasi_priorita.php
routes/console.php (schedule)
```

### Agente 4 — Schema DB: tabelle mancanti
Lancia un agente `Explore` per confrontare i dati visualizzati nelle view con le tabelle DB:
- Leggere TUTTE le migration in `database/migrations/`
- Leggere TUTTE le view Blade in `resources/views/` (cercare dati mostrati)
- Confrontare: ogni dato mostrato ha una colonna nel DB?
- Cercare variabili calcolate che vengono passate alle view ma non hanno tabella
- Cercare `compact()` nei controller per vedere quali variabili vanno alle view

## FASE 2: REPORT

Dopo che tutti gli agenti hanno terminato, genera un report strutturato:

### Sezione 1: Dati senza DB
| # | Dato | Sorgente | Dove usato | Impatto | Fix proposto |
|---|------|----------|------------|---------|-------------|

### Sezione 2: Cause di lentezza
| # | Problema | File:riga | Impatto stimato | Fix proposto | Complessità |
|---|----------|-----------|-----------------|-------------|-------------|

### Sezione 3: Migration da creare
Per ogni dato mancante, proponi:
- Nome tabella
- Colonne con tipo
- Logica di sync (cron? evento? on-demand?)
- Dove aggiornare il codice per leggere dal DB

### Sezione 4: Quick Wins (implementabili subito)
Lista ordinata per impatto/sforzo:
1. Fix con impatto alto e sforzo basso (1-5 righe di codice)
2. Fix con impatto alto e sforzo medio (migration + model + controller)
3. Fix con impatto medio

## REGOLE
- Ogni affermazione DEVE avere file:riga come riferimento
- Non proporre soluzioni che richiedono nuove dipendenze composer/npm
- Preferire soluzioni che usano il DB MySQL esistente
- I dati real-time (stato stampante) possono restare in cache, ma lo storico DEVE essere nel DB
- Considerare che il server è Windows con XAMPP, MySQL, PHP 8.5
- Le migration devono funzionare con MySQL 8.x
- Il cron Laravel gira ogni minuto tramite Task Scheduler di Windows
