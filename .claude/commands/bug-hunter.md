# Bug Hunter — MES Grafica Nappa

Agisci come un Senior QA Engineer e Security Auditor specializzato in applicazioni Laravel/PHP enterprise.

## OBIETTIVO
Analizzare il codebase del MES per identificare bug, vulnerabilità, race condition, edge case e problemi di affidabilità. Ogni bug trovato deve essere classificato, spiegato e deve avere una proposta di fix.

## FASE 1: ANALISI MULTI-AGENTE (lancia in parallelo)

### Agente 1 — Race Condition & Concorrenza
Lancia un agente `Explore` per cercare nel codebase:
- Campi usati sia come stato numerico che stringa (es. campo `stato` usato per pausa)
- Operazioni non atomiche su record condivisi (due sync che modificano lo stesso record)
- `save()` senza lock su record che possono essere modificati da più processi
- Polling che sovrascrivono dati appena modificati dall'utente
- `firstOrCreate` / `updateOrCreate` senza unique constraint nel DB
- Job schedulati che possono sovrapporsi (`withoutOverlapping` mancante)

Pattern da cercare:
```
$fase->stato =    (senza lock)
->save()          (dopo lettura non protetta)
Race condition tra sync Onda, Prinect, Fiery e azioni operatore
```

### Agente 2 — Logica di Business & Edge Case
Lancia un agente `Explore` per analizzare:
- `FaseStatoService` — cascata stati: cosa succede se una fase viene saltata?
- `PrinectSyncService` — terminazione automatica: falsi positivi/negativi ancora possibili?
- `FierySyncService` — stessi pattern di terminazione errata
- `OndaSyncService` — dedup: ci sono casi non coperti? Commesse con stesso numero ma anno diverso?
- Calcolo ore lavorate — overflow, valori negativi, divisione per zero?
- Calcolo priorità — `null` values, commesse senza data consegna?
- `propagaConsegnato()` — sovrascrive data_fine su fasi mai iniziate?
- Fasi con stato stringa (motivo pausa) — tutti i `where('stato', '<', 3)` gestiscono questo caso?

Pattern da cercare:
```
where('stato', '<', 3)     — funziona con stringhe?
where('stato', '>=', 0)    — funziona con stringhe?
$fase->stato == 2           — strict comparison con stringa?
is_numeric($fase->stato)   — è usato ovunque serve?
```

### Agente 3 — Query N+1 & Performance
Lancia un agente `Explore` per cercare:
- Query dentro loop `@foreach` nelle view Blade (N+1)
- `->get()` senza `->with()` su relazioni usate nel template
- Query senza indice su colonne filtrate frequentemente
- Tabelle senza indice su foreign key
- `GROUP_CONCAT` senza `SEPARATOR` o con limite default (1024 byte)
- `Carbon::parse()` chiamato ripetutamente sullo stesso valore
- Collection `->filter()` dopo `->get()` invece di WHERE nel query builder

Pattern da cercare:
```
@foreach($fasi as $fase)
    {{ $fase->ordine->    (N+1 se ordine non eager loaded)
    {{ $fase->faseCatalogo->   (N+1)
    {{ $fase->operatori   (N+1)
```

### Agente 4 — Sicurezza & Validazione
Lancia un agente `Explore` per cercare:
- Input utente non validato (contenteditable → aggiornaCampo senza sanitizzazione)
- XSS: `{!! !!}` o `v-html` o output non escaped
- SQL injection: `DB::raw()` con input utente, `whereRaw()` con concatenazione
- IDOR: endpoint che accettano ID senza verificare ownership
- CSRF: form o fetch senza token
- Credenziali hardcoded (password, token, chiavi API nel codice)
- File upload senza validazione tipo/dimensione
- Route senza middleware di autenticazione che dovrebbero averlo
- `$e->getMessage()` esposto all'utente (information disclosure)
- Endpoint BRT/tracking accessibili senza auth

Pattern da cercare:
```
DB::raw("... $    (SQL injection)
{!! $             (XSS)
$request->input(  (senza validate)
Route::          (senza middleware)
$e->getMessage() (in response JSON)
```

### Agente 5 — Consistenza Dati & Integrità
Lancia un agente `Explore` per cercare:
- Foreign key mancanti nelle migration (relazioni senza constraint)
- Cascade delete mancante (eliminare ordine senza eliminare fasi?)
- Campi nullable che non dovrebbero esserlo
- Valori default mancanti
- Indici unique mancanti dove servono (es. endpoint + operatore_id in push_subscriptions)
- Timestamp inconsistenti (alcune tabelle con timestamps(), altre senza)
- Soft delete su alcune tabelle ma non su altre correlate

### Agente 6 — JavaScript & Frontend
Lancia un agente `Explore` per cercare nelle view Blade:
- `setInterval` senza `clearInterval` (memory leak)
- Event listener aggiunti in loop senza rimozione
- `localStorage`/`sessionStorage` senza try/catch (può fallire in incognito)
- Fetch senza `.catch()` (promise non gestite)
- `innerText` / `innerHTML` usati in modo intercambiabile (XSS via contenteditable)
- Variabili globali che possono collidere tra pagine
- Audio context creato senza user gesture (bloccato da browser)
- `document.querySelector` su elementi che potrebbero non esistere (null pointer)

## FASE 2: CLASSIFICAZIONE BUG

Per ogni bug trovato, compila questa scheda:

```
### Bug #XX — [Titolo breve]
- **Severità**: CRITICO / ALTO / MEDIO / BASSO
- **Tipo**: Race condition / Logica / Performance / Sicurezza / Integrità / Frontend
- **File**: path/to/file.php:riga
- **Descrizione**: Cosa succede e quando
- **Impatto**: Cosa può andare storto in produzione
- **Riproduzione**: Come triggerare il bug
- **Fix proposto**: Codice o approccio per risolvere
- **Effort**: Basso (< 30min) / Medio (1-2h) / Alto (> 2h)
```

Classificazione severità:
- **CRITICO**: Corrompe dati, blocca produzione, vulnerabilità sfruttabile
- **ALTO**: Dati errati visibili all'utente, funzionalità rotta in scenari comuni
- **MEDIO**: Edge case raro, performance degradata, UX confusa
- **BASSO**: Inconsistenza cosmetica, best practice non seguita, tech debt

## FASE 3: REPORT FINALE

Dopo l'analisi, genera:
1. **Dashboard bug**: tabella riepilogativa con severità, tipo, file, effort
2. **Top 5 priorità**: i bug più urgenti da fixare subito
3. **Quick wins**: bug con effort basso e impatto alto
4. **Debt tecnico**: problemi strutturali da pianificare
5. **Confronto con audit precedente**: quali bug del security audit (16 marzo) sono ancora aperti?

## CONTESTO PROGETTO

- **Stack**: Laravel 11, PHP 8.5, MySQL, SQL Server (Onda), Apache, Windows Server
- **Integrazioni**: Prinect REST API, Fiery REST API, Onda SOAP/ODBC, BRT SOAP
- **Sync**: Onda ogni ora, Excel ogni 2 min, Fiery ogni minuto, Prinect ogni 5 min, Presenze ogni minuto
- **Utenti concorrenti**: ~30 operatori, 2 owner, 1 spedizione, sync automatiche
- **Database**: MySQL sul server .60, ~100 tabelle

## BUG GIÀ NOTI (da verificare se ancora aperti)

- Bug #14: Race condition pausa — campo `stato` contiene stringa motivo pausa invece di intero
- Bug #11: `propagaConsegnato()` sovrascrive data_fine su fasi mai iniziate
- Bug #12: `$fasiInfo` duplicato tra controller e config
- Security audit 16 marzo: 1 critico (credenziali NetTime), 3 alti (auth BRT, messaggi errore, XSS), 4 medi, 3 bassi

## COME USARE

Quando l'utente invoca `/bug-hunter`:
1. Chiedi se vuole analisi completa o focus su un'area specifica
2. Lancia gli agenti in parallelo
3. Compila il report con le schede bug
4. Proponi i fix in ordine di priorità
5. Se l'utente approva, implementa i fix uno alla volta con test
