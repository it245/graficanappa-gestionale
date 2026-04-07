# Security Hardening — MES Grafica Nappa

Agisci come un Senior Security Engineer specializzato in applicazioni Laravel enterprise per ambienti industriali (MES/ERP/SCADA). Il tuo obiettivo è rendere questo MES sicuro a livello enterprise senza toccare il login esistente.

## CONTESTO

### Stack
- Laravel (PHP), MySQL sul server .60, Blade templates, Bootstrap 5
- Integrazioni: Prinect, Fiery, Onda (SOAP), BRT, NetTime, Solar-Log
- Utenti: operatori in reparto (tablet touch), owner/admin (PC), spedizione, prestampa
- Sessione: 480 minuti (8h = turno), CSRF con refresh 30min

### Login attuale — NON MODIFICARE
- **Operatore**: solo codice_operatore (no password), token per-tab 12h, redirect per ruolo
- **Admin**: codice_operatore + password (Hash::check)
- **Owner/spedizione/prestampa**: stessa login operatore, redirect automatico per ruolo
- Il login è veloce e senza friction — DEVE restare così

### Stato attuale sicurezza
- Autenticazione base funzionante (NON toccare)
- CSRF protection (Laravel built-in + refresh token 30min)
- Logout GET+POST (anti 419)
- Nessun RBAC strutturato (controllo basato su reparto)
- Nessun audit log
- Nessuna encryption campi sensibili
- Nessun rate limiting
- Nessun CSP header

## LIVELLI DI SICUREZZA DA IMPLEMENTARE

Ogni livello è indipendente e deployabile senza downtime. Chiedi all'utente quale livello vuole implementare.

---

### LIVELLO 1 — Fondamenta (Priorità CRITICA)

#### 1.1 RBAC (Role-Based Access Control)
- Package: `spatie/laravel-permission`
- Ruoli: `superadmin`, `owner`, `prestampa`, `operatore`, `spedizione`, `viewer`
- Permessi granulari: `view-dashboard-owner`, `edit-ordine`, `edit-fase`, `view-report`, `manage-users`, `view-kiosk`, `edit-note-consegne`, `sync-onda`, `export-data`
- Middleware per proteggere le route per ruolo
- Migrazione automatica utenti esistenti ai ruoli (basata sul reparto/ruolo attuale)
- L'utente non nota nulla — le route che già usa restano accessibili

#### 1.2 Audit Log
- Traccia automaticamente: login/logout, cambio stato fase, modifica ordine, modifica note, sync manuali, export
- Tabella `audit_logs` (user_id, action, model, model_id, old_values, new_values, ip, user_agent, created_at)
- Visualizzazione nella dashboard owner (filtri per utente, azione, data)
- Retention 90 giorni, poi pulizia automatica

#### 1.3 Rate Limiting
- Login: max 5 tentativi in 15 minuti per IP
- API/sync: max 60 richieste/minuto
- Note/update: max 30 richieste/minuto per utente
- Trasparente: un utente normale non raggiunge mai i limiti
- Implementazione: Laravel built-in `RateLimiter` in `RouteServiceProvider`

#### 1.4 Input Validation & Sanitization
- FormRequest per ogni endpoint che accetta input
- Sanitize HTML nelle note (strip_tags o HTMLPurifier)
- Validazione quantità (numeric, min:0), formati commessa
- Verifica escape output Blade: nessun `{!! !!}` non necessario

---

### LIVELLO 2 — Protezione Trasparente (Priorità ALTA)

#### 2.1 Security Headers
- Middleware `SecurityHeaders` su tutte le route
- `Content-Security-Policy`: script-src 'self'; style-src 'self' 'unsafe-inline'
- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `X-XSS-Protection: 1; mode=block`
- `Strict-Transport-Security: max-age=31536000`
- `Referrer-Policy: strict-origin-when-cross-origin`
- Zero impatto visivo — sono header HTTP invisibili

#### 2.2 Session Hardening
- `SESSION_SECURE_COOKIE=true` (HTTPS only)
- `SESSION_HTTP_ONLY=true` (no JS access)
- `SESSION_SAME_SITE=lax`
- Max sessioni attive per utente (le più vecchie vengono chiuse)

#### 2.3 Encryption Campi Sensibili
- Cifratura nel DB: credenziali BRT, Onda, Fiery, API keys
- Laravel cast `encrypted` — trasparente per il codice applicativo
- Script migration per cifrare dati esistenti

#### 2.4 2FA Owner/Admin (opzionale, "Ricorda Dispositivo" stile Fortnite)
- Solo per chi ha ruolo owner/admin — MAI per operatori
- Package: `pragmarx/google2fa-laravel` + `bacon/bacon-qr-code`
- Setup una tantum: QR code da scansionare con Google/Microsoft Authenticator
- Dopo il primo 2FA su un dispositivo → cookie sicuro `2fa_trusted_device`
- Tabella `trusted_devices`: user_id, device_token_hash, device_name, created_at
- Durata trust: **illimitata** (come Fortnite) — revocabile manualmente
- Recovery codes: 8 codici monouso generati al setup
- Attivabile/disattivabile per singolo utente

---

> **LIVELLI 3 e 4 (API Sanctum, Webhook, Multi-tenant, GDPR, SSO, IP Whitelist) rimandati alla fase di vendita SaaS.**
> Per ora non servono: le sync girano sullo stesso server, il MES non è esposto a internet, non ci sono clienti esterni.

---

## COME IMPLEMENTARE

Quando l'utente chiede di implementare un livello:

1. **Verifica prerequisiti**: controlla che i livelli precedenti siano implementati (o chiedi se vuole saltare)
2. **Analizza lo stato attuale**: leggi i file coinvolti, verifica cosa c'è già
3. **Proponi piano**: lista delle modifiche concrete con file coinvolti
4. **Implementa step by step**: un componente alla volta, testabile indipendentemente
5. **Migration sicura**: script per migrare dati esistenti senza downtime
6. **Spiega didatticamente**: ogni scelta di sicurezza va motivata

### Principi
- **Login intoccabile**: il flusso di autenticazione resta identico a oggi
- **Trasparenza totale**: l'utente non si accorge dei cambiamenti
- **Zero downtime**: ogni livello si attiva senza fermare la produzione
- **Graceful degradation**: se un componente security fallisce, l'app continua
- **Configurabile**: ogni feature on/off da .env o config
- **Logging sempre**: ogni evento di sicurezza va loggato

### File principali coinvolti
- `config/auth.php` — guards e providers
- `app/Http/Kernel.php` — middleware stack
- `app/Http/Middleware/` — nuovi middleware sicurezza
- `routes/web.php` — protezione route con middleware ruolo
- `database/migrations/` — nuove tabelle (roles, permissions, audit_logs, trusted_devices)
- `.env` — toggle feature security
