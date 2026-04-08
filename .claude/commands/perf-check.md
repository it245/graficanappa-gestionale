# Performance & Fluidity Check — MES Grafica Nappa

Agisci come un Senior Performance Engineer specializzato in applicazioni web Laravel/PHP enterprise.

## OBIETTIVO
Analizzare, diagnosticare e risolvere problemi di performance, fluidità e tempi di caricamento del MES. Ogni pagina deve caricarsi in meno di 3 secondi e le interazioni devono essere istantanee.

## FASE 1: ANALISI AUTOMATICA (Multi-Agente)

### Agente 1 — Asset & Network
Lancia un agente `Explore` per analizzare:
- **Layout MES** (`resources/views/layouts/mes.blade.php`): quanti CSS/JS esterni vengono caricati
- **CDN vs locale**: quali librerie sono caricate da CDN (latenza) vs locale
- **Script non necessari**: html5-qrcode caricato su pagine che non lo usano, Choices.js dove non serve
- **Font loading**: Google Fonts bloccante vs preload/swap
- **Echo/WebSocket**: se Reverb non è attivo, lo script Echo genera errori e retry continui
- Immagini: dimensioni, formato, lazy loading mancante
- CSS inline vs file: quanti KB di CSS sono inline nelle view

Pattern da cercare:
```
<link href="https://     (risorse esterne)
<script src="https://    (script esterni)
<script src="http://     (script non HTTPS)
@include('partials.echo  (Echo client caricato ovunque)
```

### Agente 2 — Query & Database
Lancia un agente `Explore` per analizzare:
- **Controller owner dashboard**: quante query vengono eseguite (N+1, eager loading)
- **Controller scheduling**: tempo di caricamento (query pesanti)
- **Controller report ore**: perché carica lentamente
- Query senza indice (`EXPLAIN` mentale)
- `->get()` senza `->select()` (carica tutte le colonne)
- Collection `->filter()` dopo `->get()` (filtra in PHP invece che in SQL)
- Conteggi con `->count()` dopo `->get()` invece di `DB::count()`

Pattern da cercare:
```
->get()                    (senza select specifico)
->get()->filter(           (filtra in PHP)
->get()->count()           (conta in PHP)
OrdineFase::with('ordine') (carica tutto?)
```

### Agente 3 — Rendering & DOM
Lancia un agente `Explore` per analizzare nelle view Blade:
- **Tabelle enormi**: quante righe vengono renderizzate server-side (>500 righe = lento)
- **setInterval** senza debounce: polling troppo frequente
- **DOM manipulation**: innerHTML in loop, reflow forzati
- **CSS che causa reflow**: `table-layout: fixed` con colonne calcolate
- Animazioni CSS pesanti
- `overflow-x: auto` con tabelle >3000px (scroll performance)
- Quanti event listener vengono aggiunti al caricamento

### Agente 4 — Blade & Server-Side
Lancia un agente `Explore` per analizzare:
- **Blade compilation**: view non cachate?
- **Config non cachata**: `config:cache` eseguito?
- **Route non cachate**: `route:cache` eseguito?
- **Session driver**: file vs database vs redis
- **Middleware stack**: quanti middleware per richiesta
- **Eager loading mancante**: relazioni caricate lazy
- PHP `error_reporting` con DEPRECATED che rallenta (phpspreadsheet)

## FASE 2: REPORT PERFORMANCE

Per ogni problema trovato, compila:

```
### Problema #XX — [Titolo]
- **Impatto**: ALTO / MEDIO / BASSO (quanto rallenta)
- **Pagine colpite**: quali URL
- **Causa**: cosa succede
- **Misurazione**: stima ms/KB risparmiati
- **Fix**: codice o configurazione
- **Effort**: Basso / Medio / Alto
```

### Classificazione impatto:
- **ALTO**: >1s di ritardo, blocca il rendering, caricamento infinito
- **MEDIO**: 200ms-1s di ritardo, percepibile dall'utente
- **BASSO**: <200ms, ottimizzazione cosmetica

## FASE 3: FIX AUTOMATICI

Dopo l'analisi, implementa i fix in ordine di impatto/effort:

### Quick Wins (implementa subito):
1. **Rimuovi script non necessari** da pagine che non li usano
2. **Font preload** con `font-display: swap`
3. **Disabilita Echo** se Reverb non è attivo
4. **Cache config/route/view** sul server
5. **Riduci polling frequency** dove possibile

### Fix Medi:
1. **Lazy load script**: carica html5-qrcode solo nella pagina etichette
2. **Eager loading**: aggiungi `->with()` dove manca
3. **Select specifici**: aggiungi `->select()` per non caricare tutte le colonne
4. **Paginazione server-side**: per tabelle >200 righe
5. **Debounce** su input di ricerca e filtri

### Fix Strutturali:
1. **Bundle locale**: scarica Bootstrap/Chart.js in locale (elimina CDN latency)
2. **Minificazione CSS inline**: estrai in file .css cachabile
3. **Redis session**: migra da file a Redis
4. **Query caching**: cache risultati pesanti (scheduling, report)
5. **Virtual scrolling**: per tabelle >500 righe

## METRICHE TARGET

| Metrica | Target | Attuale (stima) |
|---------|--------|-----------------|
| First Contentful Paint | <1.5s | ~3-5s |
| Largest Contentful Paint | <2.5s | ~5-8s |
| Time to Interactive | <3s | ~5-10s |
| Cumulative Layout Shift | <0.1 | ~0.3 |
| Total JS caricato | <500KB | ~1.5MB |
| Total CSS caricato | <200KB | ~500KB |
| Query per pagina | <20 | ~50-100 |

## CONTESTO TECNICO

- **Server**: Windows Server, Apache, PHP 8.5, MySQL (stesso server .60)
- **Client**: PC ufficio + tablet/telefoni operatori (connessione LAN/WiFi)
- **Browser**: Chrome prevalente
- **Pagine critiche**: Owner dashboard, Operatore dashboard, Scheduling, Spedizione
- **Database**: ~100 tabelle, ~6000 ordini, ~900 fasi attive

## COME USARE

Quando l'utente invoca `/perf-check`:
1. Lancia gli agenti di analisi in parallelo
2. Compila il report con i problemi trovati
3. Classifica per impatto/effort
4. Implementa i quick wins immediatamente
5. Proponi i fix medi per approvazione
6. Documenta i fix strutturali per il futuro

## VINCOLI
- NON modificare logica di business
- NON rompere funzionalità esistenti
- NON rimuovere feature
- Testare ogni fix prima di committare
- Priorità: non rallentare nulla nel tentativo di velocizzare
