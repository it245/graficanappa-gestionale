# Performance Audit — MES Grafica Nappa

Agisci come un Senior Performance Engineer specializzato in applicazioni Laravel/PHP/MySQL enterprise.

## OBIETTIVO
Analizzare il codebase del MES per identificare colli di bottiglia, query lente, N+1, memory leak, caricamenti inutili e opportunità di caching. Ogni problema trovato deve essere classificato con impatto stimato e proposta di fix.

## FASE 1: ANALISI MULTI-AGENTE (lancia tutti in parallelo)

### Agente 1 — Query N+1 & Eager Loading
Lancia un agente `Explore` per cercare in TUTTI i controller e le view Blade:
- `@foreach` che accedono a relazioni non eager-loaded (`$fase->ordine->`, `$fase->faseCatalogo->`, `$fase->operatori`)
- `->get()` senza `->with()` quando le relazioni vengono usate dopo
- Query ripetute per lo stesso dato nel controller (stessa tabella interrogata più volte)
- `::find()` o `::where()->first()` dentro loop
- Conteggi ripetuti (`->count()` su collection invece di `COUNT()` SQL)

File prioritari:
```
app/Http/Controllers/DashboardOwnerController.php
app/Http/Controllers/DashboardOperatoreController.php
app/Http/Controllers/DashboardSpedizioneController.php
app/Http/Controllers/KioskController.php
app/Http/Controllers/PresenzeController.php
app/Services/OndaSyncService.php
app/Http/Services/PrinectSyncService.php
```

### Agente 2 — Query Lente & Indici Mancanti
Lancia un agente `Explore` per analizzare:
- Tutte le migration per verificare indici su colonne usate in WHERE, JOIN, ORDER BY
- `whereHas()` nested (genera subquery lente — meglio JOIN)
- `whereRaw("REGEXP")` usato ovunque per il bug stato stringa — è lento
- `GROUP_CONCAT` senza SEPARATOR (limite 1024 byte)
- `LIKE '%...'` (leading wildcard, non usa indice)
- `DB::connection('onda')->select()` — query SQL Server senza TOP o paginazione
- `Carbon::parse()` in loop (costoso, meglio cache o pre-parse)
- `->orderBy()` su colonne non indicizzate
- `->groupBy()` + `->having()` senza indice

Cerca nelle migration:
```
Schema::create    — quali tabelle hanno indici?
$table->index     — dove sono definiti?
$table->foreign   — foreign key presenti?
```

### Agente 3 — Caching & Chiamate Ripetute
Lancia un agente `Explore` per cercare:
- Dati che vengono calcolati ad ogni richiesta ma cambiano raramente (config, reparti, fasi_catalogo)
- API esterne chiamate senza cache (Prinect, Fiery, BRT, Solar-Log, Onda)
- `Reparto::where('nome', '...')` ripetuto in ogni controller (dovrebbe essere cached)
- `FasiCatalogo::where(...)` ripetuto
- `config()` lento se non cached (`php artisan config:cache`)
- View che non usano `@once` per JS/CSS ripetuti
- `DB::table()` per query statiche che potrebbero essere cached 5-10 minuti

### Agente 4 — Payload & Frontend
Lancia un agente `Explore` per analizzare:
- Dashboard owner: quanti dati carica? `->get()` su tutte le fasi attive → quante righe?
- `compact()` nelle view: vengono passate collection enormi non filtrate?
- JavaScript: `setInterval` con polling frequente (ogni 1s, 5s, 15s, 30s) — quanti timer attivi?
- `fetch()` polling: quante richieste/minuto genera il browser per ogni dashboard aperta?
- CSS inline ripetuto (stesso `<style>` in ogni view)
- Bootstrap/CDN: viene caricato per intero o solo i componenti usati?
- Immagini non ottimizzate
- DOM pesante (troppe righe in tabella senza paginazione/virtual scroll)

### Agente 5 — Sync & Background Jobs
Lancia un agente `Explore` per analizzare:
- `OndaSyncService::sincronizza()` — quante query esegue per sync? Usa batch insert o singoli save()?
- `PrinectSyncService` — chiama API Prinect per ogni commessa singolarmente?
- `FierySyncService` — stessi pattern
- `SyncPresenze` — parsing file ogni minuto, quanto è pesante?
- `Excel import/export` — carica tutto in memoria o usa chunk?
- Job schedulati: si sovrappongono? `withoutOverlapping()` è usato?
- Lock/mutex sui sync per evitare esecuzioni parallele

### Agente 6 — Memory & Scalabilità
Lancia un agente `Explore` per cercare:
- `->get()` che caricano migliaia di record in memoria (senza `->chunk()` o `->cursor()`)
- Collection enormi manipolate con `->map()`, `->filter()`, `->groupBy()` in memoria
- `PhpSpreadsheet` — export Excel carica tutto in RAM?
- SOAP client per Onda/BRT — connection pooling o nuova connessione ogni volta?
- Log file: `Log::info()` chiamato in loop (riempie disco)
- `$fasiViste[]` array che cresce senza limite nel sync
- View compilate: `php artisan view:cache` usato?
- Route cache: `php artisan route:cache` usato?

## FASE 2: CLASSIFICAZIONE PROBLEMI

Per ogni problema trovato, compila questa scheda:

```
### Perf #XX — [Titolo breve]
- **Impatto**: CRITICO / ALTO / MEDIO / BASSO
- **Tipo**: N+1 / Query lenta / Caching / Frontend / Sync / Memory
- **File**: path/to/file.php:riga
- **Descrizione**: Cosa succede e perché è lento
- **Misura**: Stima query/richieste/MB impattati
- **Fix proposto**: Codice o approccio per risolvere
- **Effort**: Basso (< 30min) / Medio (1-2h) / Alto (> 2h)
- **Guadagno stimato**: es. "da 200 query a 5", "da 3s a 200ms"
```

Classificazione impatto:
- **CRITICO**: Pagina > 5s, sync che blocca il server, memory overflow
- **ALTO**: Pagina 2-5s, polling eccessivo, 100+ query per pagina
- **MEDIO**: Pagina 1-2s, caching mancante su dati stabili, 50+ query
- **BASSO**: Ottimizzazione minore, best practice, < 50 query extra

## FASE 3: REPORT FINALE

Dopo l'analisi, genera:
1. **Mappa calore**: quali pagine/endpoint sono più lenti e perché
2. **Top 10 fix**: ordinati per rapporto impatto/effort
3. **Quick wins**: fix sotto 30 minuti con alto impatto
4. **Piano caching**: cosa mettere in cache, per quanto tempo, strategia invalidazione
5. **Piano indici**: ALTER TABLE da eseguire sul DB
6. **Piano polling**: riduzione richieste polling con WebSocket o intervalli più lunghi
7. **Benchmark**: metriche prima/dopo per ogni fix applicato

## CONTESTO PROGETTO

- **Stack**: Laravel 11, PHP 8.5, MySQL 8 (server .60), SQL Server (Onda .253), Apache, Windows Server
- **Integrazioni**: Prinect REST API, Fiery REST API, Onda ODBC, BRT SOAP, Solar-Log HTTP
- **Sync schedulati**: Onda ogni ora, Excel ogni 2 min, Fiery ogni minuto, Prinect ogni 5 min, Presenze ogni minuto
- **Utenti concorrenti**: ~30 operatori, 2 owner, 1 spedizione, 1 kiosk TV
- **Database**: ~600 ordini attivi, ~3000 fasi attive, ~30k ordini storici
- **Problemi noti**: dashboard owner lenta su telefono, sync Onda impiega 10-15s, kiosk ricarica ogni 2 min

## COME USARE

Quando l'utente invoca `/perf-audit`:
1. Chiedi se vuole analisi completa o focus su un'area specifica (dashboard, sync, frontend, DB)
2. Lancia gli agenti in parallelo
3. Compila il report con le schede problema
4. Proponi i fix in ordine di rapporto impatto/effort
5. Se l'utente approva, implementa i fix uno alla volta, misurando il miglioramento
