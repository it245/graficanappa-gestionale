# Test v2.0 — MES Grafica Nappa

Testa ogni angolo della v2.0 del MES attraverso agenti paralleli specializzati. Ogni agente verifica un'area specifica e riporta problemi trovati.

## CONTESTO
- **Branch**: def2.0
- **Stack**: Laravel 12, PHP 8.5, MySQL, Bootstrap 5
- **Server**: Windows Server .60, IIS
- **DB**: MySQL locale sul server .60, SQL Server Onda su .253

## COME USARE

Quando l'utente invoca `/v2-test`, lancia 6 agenti in parallelo:

### Agente 1: Route & Controller Test
Verifica che tutte le route definite in `routes/web.php` abbiano:
- Controller e metodo esistenti
- Middleware corretto (auth, role check)
- Nessuna route orfana (controller mancante)
- Nessun metodo mancante nel controller
- Verifica che le named route usate nelle viste (`route('nome')`) esistano tutte

### Agente 2: Blade Views Test
Verifica tutte le viste in `resources/views/`:
- Nessuna variabile non passata dal controller (`$undefined`)
- Tutti i `@include` puntano a file esistenti
- Tutti i `route('nome')` nelle viste corrispondono a route definite
- Nessun `{!! !!}` pericoloso senza escape (XSS)
- Layout corretto (`@extends`, `@section`, `@yield`)
- Nessun file blade orfano (non referenziato)

### Agente 3: Model & Database Test
Verifica tutti i model in `app/Models/`:
- Relazioni corrette (le tabelle e FK esistono nelle migration)
- `$fillable` e `$guarded` coerenti
- Nessun campo in `$fillable` che non esiste nella migration
- Cast corretti
- Nessun N+1 query evidente nei controller (mancano `with()`)

### Agente 4: Service Logic Test
Verifica tutti i service in `app/Services/`:
- Metodi chiamati dai controller esistono nel service
- Transazioni DB corrette (uso di `DB::transaction`)
- Gestione errori (try/catch dove serve)
- Nessun hardcoded value (credenziali, path)
- Import/use corretti (classi referenziate esistono)

### Agente 5: Magazzino Module Test
Test specifico del modulo magazzino appena creato:
- Tutte le 5 migration sono coerenti tra loro (FK valide)
- I 5 model hanno relazioni corrette
- I 5 controller passano tutte le variabili necessarie alle viste
- Le viste usano solo variabili passate dal controller
- Il FabbisognoService collega correttamente ordini.cod_carta a magazzino_articoli.codice
- L'OCR service gestisce errori correttamente
- Il QR service genera PDF validi
- Le route magazzino hanno il middleware MagazzinoAuth

### Agente 6: Security Regression Test
Verifica che i fix di sicurezza applicati oggi siano ancora attivi:
- MagazzinoAuth middleware controlla reparto spedizione
- Open redirect fixato (solo URL interni con `/`)
- File upload in storage privato (non public)
- SQL injection LIKE con escape wildcards
- CSRF endpoint protetto da auth
- Prinect explorer protetto da owner.or.admin
- Token auth verifica operatore attivo
- Mass assignment: ruolo/attivo/password in $guarded
- Exception messages non esposti all'utente
- JSON encode con flag HEX nelle viste

## OUTPUT

Ogni agente deve riportare:
- **OK**: item verificati senza problemi
- **WARN**: potenziali problemi non bloccanti
- **ERROR**: problemi che rompono funzionalita

Formato: tabella con File, Riga, Tipo (OK/WARN/ERROR), Descrizione

Al termine, consolidare i risultati in un report unico con priorita di fix.
