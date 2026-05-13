# 09. Sicurezza e GDPR

## Autenticazione

### Dual-guard Laravel

| Guard | Provider | Tabella | Accesso |
|---|---|---|---|
| `web` | `users` | `users` | Admin → `/admin/*` |
| `operatore` | `operatori` | `operatori` | Staff → `/operatore/*`, `/produzione/*`, `/spedizione/*`, `/magazzino/*`, `/chat/*` |

Configurazione: `config/auth.php`.

### Login operatore

- **Credenziali**: `codice_operatore` (alfanumerico, es. "RB", "MIR") + password opzionale
- **Token per-tab**: `OperatoreToken` (12h expiry) via `op_token` query/header → permette sessioni multiple su tab diversi
- **Fallback**: `session('operatore_id')` se token mancante
- **Throttle**: rate limit `throttle:login` (60 req/min)

### Login admin con 2FA TOTP

- **Step 1**: email + password → `AdminLoginController`
- **Step 2**: se `users.2fa_enabled_at` non-null → redirect `/admin/2fa/challenge`
- **Step 3**: codice TOTP (6 cifre da Google Authenticator / Authy)
- **Step 4**: verifica via `pragmarx/google2fa` (PragmaRX)
- **Recovery codes**: 8 codici 4-4 one-time use, generati al setup
- **Trusted devices**: cookie 10 anni SHA256 hash → tabella `trusted_devices`, evita 2FA su device noti

### Sessioni

| Voce | Valore |
|---|---|
| Driver | `database` (env `SESSION_DRIVER`) |
| Lifetime | 480 minuti (env `SESSION_LIFETIME`) |
| Encryption | `true` (env `SESSION_ENCRYPT`) |
| Secure cookie | `true` in prod (`SESSION_SECURE_COOKIE`) |
| Same-site | `lax` default |

### CSRF

- **Token refresh** ogni 30min via `/csrf-refresh` (operatore views)
- **Pageshow handler**: ricarica token su back/forward browser
- **CSRF exempt routes** (token-based o webhook):
  - `owner/aggiungi-riga`, `owner/sync-onda`, `owner/import`, `owner/aggiorna-campo`, `owner/cliche/*`, `owner/applica-priorita`
  - `operatore/prestampa/*`
  - `spedizione/sync-onda`
  - `telegram/webhook/*` (HMAC secret)

**File auth coinvolti:**
- `config/auth.php`
- `app/Http/Controllers/AdminLoginController.php`
- `app/Http/Controllers/OperatoreLoginController.php`
- `app/Http/Controllers/TwoFactorController.php`
- `app/Services/TwoFactorService.php`
- `app/Models/OperatoreToken.php`
- `bootstrap/app.php` (CSRF exceptions, 419 handler)

## Middleware sicurezza

| Middleware | Path | Scopo |
|---|---|---|
| `SecurityHeaders` | global append | HSTS, CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy |
| `GzipResponse` | global append | Compressione output (-70% bytes, -1s LCP) |
| `AdminAuth` | `/admin/*` | Auth admin + token fallback |
| `OperatoreAuth` | `/operatore/*`, `/produzione/*`, `/spedizione/*`, `/chat/*` | Auth operatore + token (`op_token`) |
| `OwnerMiddleware` | `/owner/*` | Check ruolo `owner` |
| `OwnerOrAdmin` | `/mes/prinect/*`, `/mes/fiery/*`, `/report-percorso` | Owner OR admin (extras opzionali: `fiery_contatori`) |
| `MagazzinoAuth` | `/magazzino/*` | Owner/owner_readonly/admin OR reparti spedizione |
| `throttle:login` | `/operatore/login`, `/admin/login` | Rate limit 60 req/min |

**Pattern token-first** (Operatore/Admin):
```
1. Cerca op_token in query/header → OperatoreToken::valido() scope
2. Se valido: imposta auth context
3. Fallback: session('operatore_id')
4. Se entrambi mancano:
   - API expect JSON → 401
   - HTML → redirect login
```

## Headers HTTP sicurezza

Tutti impostati da `SecurityHeaders` middleware:

| Header | Valore | Scopo |
|---|---|---|
| `Strict-Transport-Security` | `max-age=31536000` (1 anno) | HSTS — solo su HTTPS attivo |
| `Content-Security-Policy-Report-Only` | `script-src 'self' 'unsafe-inline' 'unsafe-eval' ...` | CSP report-only (legacy onclick/Echo) |
| `X-Frame-Options` | `SAMEORIGIN` | Anti-clickjacking |
| `X-Content-Type-Options` | `nosniff` | Anti MIME-sniffing |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Privacy |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=()` | Blocca API browser sensibili |

**Note:**
- HSTS attivato solo dopo verifica HTTPS stabile (env `force_https=true`)
- CSP **Report-Only** (non bloccante) per via codice legacy con `unsafe-inline`/`unsafe-eval` → migrazione completa programmata
- Trusted proxies: env `TRUSTED_PROXIES` (default `*` per tunnel tnnl.in, in prod whitelist `<host>,192.168.1.0/24`)

## Audit log

### Tabella `audit_logs`

```sql
id          BIGINT PK
user_id     BIGINT NULL  -- non FK (log conservato dopo eliminazione utente)
user_name   VARCHAR(100)
action      VARCHAR(50)
model       VARCHAR(100)
model_id    BIGINT
old_values  JSON
new_values  JSON
ip          VARCHAR(45)
user_agent  VARCHAR(500)
extra       TEXT
created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```

Indici: `user_id`, `action`, `model`, `created_at`, composite `(model, model_id)`.

### Action types (`TipoAzione` enum)

`Create`, `Read`, `Update`, `Delete`, `Login`, `Logout`, `Export`, `Sync`, `Failed`

### Eventi tracciati (`Listeners`)

| Listener | Trigger |
|---|---|
| `LogFaseAvviata` | `FaseAvviata` event |
| `LogFaseTerminata` | `FaseTerminata` event |
| `LogCommessaCompletata` | `CommessaCompletata` event |
| `LogMovimentoMagazzino` | `SottoSogliaEvento`, movimento manuale |
| `LogLoginRiuscito` | Auth event |
| `LogLoginFallito` | Auth failed event |

### Livelli sicurezza (`LivelloSicurezza` enum)

| Livello | Retention | Esempi |
|---|---|---|
| `Normale` | 1095 giorni (3 anni) | Fase avviata/terminata, modifiche standard |
| `Sensibile` | 730 giorni (2 anni) | Login, modifiche ruolo, password reset |
| `Critico` | 2555 giorni (7 anni) | Eliminazioni, esportazioni dati, accessi audit log |

### Mask dati sensibili (`DatiSensibiliRule`)

Pre-storage automatico mask:
- `password` → `[REDACTED]`
- `token`, `bearer` → `[REDACTED]`
- `sk-*` (API keys) → `[REDACTED]`
- `api_key`, `secret` → `[REDACTED]`

Applicato in `AuditLogService::log()` su `old_values`/`new_values`/`extra` JSON.

### Comando retention

```
php artisan audit:pulisci         # default 90gg
php artisan audit:pulisci --giorni=365
```

Cron giornaliero 03:00 (vedi `08-deployment.md`).

**File modulo Audit:**
- `app/Modules/Audit/Services/AuditLogService.php`
- `app/Modules/Audit/Services/AuditQueryService.php`
- `app/Modules/Audit/Services/ComplianceExportService.php`
- `app/Modules/Audit/Rules/DatiSensibiliRule.php`
- `app/Modules/Audit/Rules/RitenzioneRule.php`
- `app/Modules/Audit/Rules/AccessoLogRule.php`
- `app/Console/Commands/PulisciAuditLog.php`

## Compliance GDPR

### Art. 15 — Diritto di accesso

```php
app(ComplianceExportService::class)->esportaPerOperatore(
    userId: 42,
    from: Carbon::now()->subYear(),
    to: Carbon::now()
);
```

Ritorna array `azione/entita/prima/dopo` per operatore.
**Esclude** `user_agent` (rischio fingerprinting). Filtrato + pseudonimizzato.

### Art. 17 — Diritto di cancellazione

Non è hard-delete. `PseudonymizationJob` anonimizza:
- `user_name` → `EX-USER-{id}`
- `ip` → ultimo octet azzerato (es. `192.168.1.0`)
- Audit log conservato per retention rule (compliance)

### Art. 4 Statuto Lavoratori — Sorveglianza non individuale

```php
app(ComplianceExportService::class)->aggregatoSindacale(
    from: Carbon::now()->subMonth(),
    to: Carbon::now()
);
```

Ritorna `[date, action, count]` **senza PII** (no nome operatore, no model_id).
Supporta review RSU/RSA collettivo — vietata sorveglianza individuale.

### Procedura sindacale obbligatoria

MES traccia produttività individuale (`OrdineFase.operatore_id` + tempi) → **richiesto** accordo RSU/RSA o autorizzazione ITL prima rollout completo (art. 4 L. 300/1970).

Status azienda :
- ✅ Informativa GDPR lavoratori art. 13 firmata da ogni dipendente
- ⏳ Accordo RSU/RSA art. 4 — TODO da memoria progetto

### Retention automatica (RitenzioneRule)

```php
RitenzioneRule::eScaduto(
    createdAt: $log->created_at,
    livello: LivelloSicurezza::Sensibile,
    now: Carbon::now()
);
```

Cron `audit:pulisci` giornaliero 03:00 elimina log scaduti per livello.

### Access control audit log (AccessoLogRule)

| Ruolo | Letture permesse | Export |
|---|---|---|
| `operatore` | Solo proprio, livello `Normale` | ❌ |
| `owner` | Tutti, livello `Normale` | ❌ |
| `admin` | Tutti, tutti livelli (Normale/Sensibile/Critico) | ✅ |
| `owner_readonly` | Tutti `Normale`, solo lettura | ❌ |

Enforced in `Policy` Laravel (`AuditLogPolicy`).

## Best practice applicate

### Password

- Hashing: `bcrypt` (default Laravel)
- Cost factor: 12 (Laravel default per PHP 8.5)
- Rotation: nessuna forzata (user-driven)

### SQL injection

- **Eloquent ORM** ovunque (bind parameters auto)
- Raw queries (`DB::select(..., [...])`) usano placeholder
- Mai `DB::raw(string user input)` senza sanitizzazione

### XSS

- **Blade escape automatico** `{{ $var }}` (htmlspecialchars)
- `{!! $var !!}` (raw) usato solo su contenuto fidato sanitizzato
- Fiery dashboard: `fieryEsc()` + `fieryStatoSafe()` per dati API esterni

### File upload

- Whitelist MIME types
- Storage isolato (`storage/app/`), non accessibile diretto
- Path traversal check

### Webhook Telegram

- HMAC-SHA256 secret in URL: `/telegram/webhook/{secret}`
- Verifica `hash_equals()` constant-time
- Secret string ≥16 caratteri (regex validate `[A-Za-z0-9_-]{16,}`)

### Rate limiting

| Route | Limit |
|---|---|
| `/operatore/login`, `/admin/login` | 60 req/min |
| API generiche | 60 req/min (default Laravel) |
| Push subscribe | 10 req/min per IP |

## Security audit interno

**Data:** 2026-05-08

| Severity | Count | Status |
|---|---|---|
| Critico | 1 | ⏳ Da risolvere prima rollout partner |
| Alto | 3 | ⏳ |
| Medio | 4 | 📝 Programmato |
| Basso | 3 | 📝 |



## Considerazioni multi-tenant per deploy partner

### Isolamento dati

| Aspetto | Opzione A (istanza dedicata) | Opzione B (multi-tenant column) |
|---|---|---|
| DB | Schema MySQL separato per tenant | Schema condiviso + `tenant_id` column |
| Codice | Codebase clonata per tenant | Codebase singola |
| Sessioni | Naturalmente isolate | Cookie domain isolato |
| Audit log | Tabella isolata | `audit_logs.tenant_id` |
| Backup | Per-tenant | Shared con filtro |
| Onboarding | Manuale (1-2h per tenant) | Automatizzato (5min) |

### Raccomandazione per partner

**Opzione A** per i primi 2-3 tenant (rischio bug multi-tenant basso, controllo dati pieno).
**Opzione B** dopo refactor completo (sistema attuale predispone).

### Checklist sicurezza pre-deploy partner

- [ ] HTTPS forzato (`force_https=true`)
- [ ] HSTS attivo
- [ ] 2FA admin obbligatorio
- [ ] Backup MySQL automatico giornaliero
- [ ] Audit log retention configurata
- [ ] CSP migrato a non-Report-Only (rimozione `unsafe-inline`/`unsafe-eval`)
- [ ] Trusted proxies whitelist (no `*` in prod)
- [ ] `.env` non in git, permessi 600
- [ ] Disaster recovery testato
- [ ] Procedura sindacale art. 4 firmata (per tenant italiani)
- [ ] Informativa GDPR art. 13 firmata da dipendenti tenant
- [ ] DPO nominato (se >250 dipendenti)
