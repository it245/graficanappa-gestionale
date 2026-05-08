# Security Audit MES — 08/05/2026

**Branch:** `def2.0`
**Auditor:** senior Laravel security engineer
**Audit precedente:** `reference_security_audit.md` (16/03/2026)

## Sommario

| Severity | Trovate | Fixate | TODO |
|----------|---------|--------|------|
| Critical | 1       | 1      | 0    |
| High     | 3       | 3      | 0    |
| Medium   | 4       | 0      | 4    |
| Low      | 3       | 0      | 3    |

---

## Critical (1) — FIXATI

### [x] FIXED — Command Injection in `SyncPresenze::ensureNetUse`
- **File:** `app/Console/Commands/SyncPresenze.php:43`
- **Problema:** `exec("net use ... /user:{$user} {$pass}")` interpolava `NETTIME_SHARE_USER`/`NETTIME_SHARE_PASS` direttamente nella shell. Se le variabili `.env` contenessero metacharacters (`;`, `|`, `&`, `` ` ``), un attaccante con write su `.env` potrebbe ottenere RCE sul server.
- **Fix:** uso di `escapeshellarg()` per entrambi i parametri + rimozione di `$out` dal log (poteva contenere la password in chiaro in caso di errore di autenticazione).
- **Note storiche:** l'audit del 16/03 segnalava "credenziali hardcoded" ma il codice attuale già usa `env(...)` — il rischio residuo è command injection.

---

## High (3) — FIXATI

### [x] FIXED — Endpoint pubblici espongono dati commerciali
- **File:** `routes/web.php`
- **Problema:** Le route seguenti erano accessibili senza autenticazione:
  - `GET /etichette` → lista commesse (clienti, descrizioni)
  - `GET /report-percorso` → riepilogo HTML commesse + clienti + qta + date
  - `GET /report-percorso/excel` → export Excel completo
  - `GET /ddt/pdf/{numeroDdt}` → PDF DDT con cliente, indirizzo, articoli
  - `GET /api/fustella-resolve` → lookup PDF fustella per codice
- **Fix:** aggiunto `operatore.auth` (rotte produzione) o `owner.or.admin` (report direzionali). I tracking BRT erano già protetti dal fix precedente del 16/03.
- **Impatto:** zero per utenti già autenticati. Eventuali link diretti a DDT PDF pubblicati esternamente vanno re-inviati dietro login.

### [x] FIXED — Mass assignment dinamico in `ProduzioneController::aggiornaCampo`
- **File:** `app/Http/Controllers/ProduzioneController.php:240`
- **Problema:** `$fase->{$request->campo} = $request->valore` su input utente. Il validator whitelistava `qta_prod|note|scarti`, ma se in futuro qualcuno allargasse la regola `in:` (ad esempio per aggiungere un nuovo campo) si aprirebbe assegnazione arbitraria a colonne sensibili (`stato`, `priorita`, `esterno`).
- **Fix:** sostituita assegnazione dinamica con `switch` esplicito. Defense-in-depth: anche se il validator passasse un valore inatteso, il controller respinge con 422.

### [x] FIXED — Command injection arguments unescaped (SyncPresenze)
- Stesso file del critico: l'esecuzione `exec()` con interpolazione era anche un rischio "high" sul fronte log poisoning (output stderr contenente la password emesso in console). Fixato contestualmente al critico (escape + log sanitization).

---

## Medium (4) — TODO

### [ ] TODO — XSS via `{!! !!}` in dashboard spedizione + owner
- **File:** `resources/views/spedizione/dashboard.blade.php:723`, `resources/views/owner/dashboard.blade.php:1115`
- **Azione:** verificare se il contenuto è HTML legittimo (es. SVG, badge custom) o se può ospitare input utente. Sostituire con `{{ }}` dove sicuro, altrimenti `{!! Purifier::clean($val) !!}`.

### [ ] TODO — IDOR potenziale su `eliminaFase`
- **File:** `app/Http/Controllers/DashboardOwnerController.php:964`
- **Stato attuale:** la route è già protetta dal middleware `owner` (gruppo route), quindi solo owner autenticati possono chiamarla. Tuttavia non c'è check di proprietà sulla fase: un owner potrebbe eliminare qualunque fase di qualunque commessa.
- **Azione:** acceptable risk se modello "owner = unico responsabile produzione". Per multi-tenant SaaS futuro va aggiunto policy check.

### [ ] TODO — `$e->getMessage()` esposto al client (informational disclosure)
- **File:** `app/Http/Services/BrtService.php:52`
- **Problema:** errori connessione BRT ritornano messaggio crudo all'API consumer (può rivelare path interni, IP, credenziali parziali).
- **Azione:** mappare a messaggi generici lato controller, mantenere `Log::error` con dettaglio.

### [ ] TODO — Limite upload file mancante su `importOrdini`
- **File:** `app/Http/Controllers/DashboardOwnerController.php` (route `owner.importOrdini`)
- **Azione:** aggiungere `'file' => 'required|file|max:10240|mimes:xlsx,xls,csv'` nel FormRequest.

---

## Low (3) — TODO

### [ ] TODO — Token operatore senza scadenza
- **File:** `app/Http/Middleware/OperatoreAuth.php`
- **Problema:** `op_token` salvato in cookie/session senza TTL → sessione indefinita.
- **Azione:** aggiungere expiration (es. 7 giorni) + rotazione su login. Rischio basso perché tokens sono codici operatore noti solo internamente.

### [ ] TODO — `verify_ssl` opzionale in `BrtService`
- **File:** `app/Http/Services/BrtService.php:24`
- **Stato:** ora default `true` (già fixato dal 16/03). Verifica solo che in production `BRT_VERIFY_SSL` non sia settato a `false`.

### [ ] TODO — Rate limit su API non-login
- **File:** `routes/web.php`
- **Azione:** aggiungere `throttle:60,1` su endpoint pubblici tipo `/health`, `/push/*` per prevenire abuse.

---

## Falsi positivi (non fixati per design)

- **`$e->getMessage()` in `DashboardOwnerController:1576`** → va in `Log::warning`, non al client. Non è una vulnerabilità.
- **`$e->getMessage()` in `MagazzinoScannerController:104`** → eccezione `RuntimeException` con messaggi business safe (es. "Quantità insufficiente").
- **`$e->getMessage()` in `ProduzioneController:177,202`** → `FaseTransitionException` con messaggi domain-safe ("Fase già avviata").
- **Endpoint `tracking BRT pubblici`** segnalato dall'audit del 16/03 → già fixato in `routes/web.php:221-222` con `operatore.auth`.
- **`Ordine` model `$fillable` troppo ampio** → tutti i campi listati sono campi business legittimi controllati dai FormRequest.
- **`/kiosk` GET no-auth** → intenzionale: dashboard TV in produzione, accesso fisico controllato.

---

## Verifica

- `php -l` clean su tutti i file modificati
- Modifiche minime, nessun cambio di comportamento UI
- Wrapper legacy (`OndaSyncService`/`PrinectService`/`FieryService`) non toccati (out of scope)

## Prossima sessione

1. XSS audit completo su `{!! !!}` (medio)
2. `$e->getMessage()` mapping a messaggi generici (medio)
3. Limite upload file (medio)
4. Token TTL operatore (basso)
5. Rate limit globale endpoint pubblici (basso)
