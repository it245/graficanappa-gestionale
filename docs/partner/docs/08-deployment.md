# 08. Deployment

## Architettura produzione

```
┌──────────────────────────────────────────────────────────┐
│  Application Server (Windows Server)                     │
│  ┌────────────────────────────────────────────────────┐  │
│  │ Apache 2.4 (FastCGI PHP 8.5)                       │  │
│  │  └─ :80 → applicazione web                          │  │
│  └────────────────────────────────────────────────────┘  │
│  ┌────────────────────────────────────────────────────┐  │
│  │ MySQL 8 (locale, porta 3306) — DB applicativo      │  │
│  └────────────────────────────────────────────────────┘  │
│  ┌────────────────────────────────────────────────────┐  │
│  │ Windows Task Scheduler                              │  │
│  │   schedule:run        (ogni minuto)                 │  │
│  │   queue worker batch  (ogni 2 minuti)               │  │
│  └────────────────────────────────────────────────────┘  │
│  ┌────────────────────────────────────────────────────┐  │
│  │ Windows service Telegram Bot (long-polling)         │  │
│  └────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘
        │                          │
        ↓                          ↓
┌─────────────────┐    ┌─────────────────────────┐
│ ERP esterno     │    │ Network shares          │
│ (SQL Server)    │    │ (presenze, export)      │
└─────────────────┘    └─────────────────────────┘
```

## Stack runtime

| Componente | Versione | Note |
|---|---|---|
| OS | Windows Server | Tipicamente 2019/2022 |
| Web server | Apache 2.4 | PHP via FastCGI |
| PHP | 8.5 (min 8.2) | Sapi: fpm/fastcgi |
| MySQL | 8.0 LTS | Engine InnoDB, utf8mb4 |
| Composer | 2.5+ | Autoload optimized in prod |
| Node | 18+ | Build asset Vite |
| Git | 2.40+ | Pull deploy |

> **IIS NON usato**: intercetta porta 80 e crea conflitti . Apache su porta 80 dedicata.

## Cron / Task Scheduler

Windows Task Scheduler esegue `\LaravelScheduler` come SYSTEM ogni minuto:

```
php artisan schedule:run
```

File: `routes/console.php` (13 task schedulati):

| # | Comando | Frequenza | Condizione |
|---|---|---|---|
| 1 | `onda:sync` | Ogni ora | 24/7 |
| 2 | `excel:sync` | Ogni 2 min | 24/7 |
| 3 | `fiery:sync` | Ogni minuto | 24/7 |
| 4 | `prinect:sync-attivita` | Ogni 5 min | 24/7 (storico 7gg) |
| 5 | `fiery:snapshot-contatori` | Feriali 16:55 | Pre-shutdown 17:00 |
| 6 | `fiery:export-contatori --mese-corrente --email={REPORT_CONTATORI_TO}` | Ultimo del mese 17:00 | Dopo snapshot |
| 7 | `presenze:sync` | Ogni minuto | Feriali 05-23 |
| 8 | `presenze:export-excel` | Ogni 15 min | Feriali 05-23 |
| 9 | `audit:pulisci` | Giornaliero 03:00 | Retention 90gg default |
| 10 | `cliche:match` | Ogni 10 min | Dopo Onda sync |
| 11 | `scheduler:run --email --to={PIANO_EMAIL_TO}` | Giornaliero `PIANO_EMAIL_ORA` | Feriali, `PIANO_EMAIL_ENABLED=true` |
| 12 | `ritardi:alert` | Feriali 07:30 | `ALERT_RITARDI_ENABLED=true` |
| 13 | `scheduler:run` (Mossa 37 ottimizzatore) | Ogni 15 min | Attivo |

### Variabili env per cron

| Variabile | Default | Uso |
|---|---|---|
| `REPORT_CONTATORI_TO` | `admin@cliente.it` | Email report contatori V900 |
| `PIANO_EMAIL_ENABLED` | `false` | Abilita email piano produzione |
| `PIANO_EMAIL_ORA` | `06:00` | Ora invio piano |
| `PIANO_EMAIL_TO` | `direzione@cliente.it` | Destinatari piano (csv) |
| `ALERT_RITARDI_ENABLED` | `false` | Abilita alert ritardi |

## Queue + Worker

| Voce | Valore |
|---|---|
| Driver | `database` (env `QUEUE_CONNECTION`) |
| Tabella jobs | `jobs` |
| Tabella failed | `failed_jobs` (driver `database-uuids`) |
| Retry after | 90s |
| Worker | `queue_worker.bat` (PHP artisan queue:work --tries=3 --timeout=300) |
| Esecuzione worker | Task Scheduler ogni 2 minuti (controllo se già in esecuzione) |

**Job principali:**
- `CachePrinectInkJob` — Caching consumo inchiostro Prinect (long-running)
- `ExcelSyncJob` — Sync bidirezionale Excel (potenzialmente lungo)
- `BollaAIJob` — Analisi foto bolla via Claude Vision (Telegram bot)

**Failed jobs management:**
```
php artisan queue:failed         # lista
php artisan queue:retry all      # retry tutti
php artisan queue:flush          # pulisci tutti
php artisan queue:retry <uuid>   # retry singolo
```

## Servizi Windows

### Mossa37TelegramBot (nssm)

Long-polling bot Telegram in background:

```
nssm install Mossa37TelegramBot "C:\php\php.exe"
nssm set Mossa37TelegramBot AppParameters "artisan telegram:poll --timeout=30"
nssm set Mossa37TelegramBot AppDirectory "C:pp"
nssm set Mossa37TelegramBot AppRestartDelay 5000
nssm set Mossa37TelegramBot Start SERVICE_AUTO_START
nssm start Mossa37TelegramBot
```

Auto-restart 5s in caso di crash. Log: `storage/logs/laravel.log`.

## File system

```
C:pp\           
├── app/
├── bootstrap/
├── config/
├── database/migrations/
├── docs/                                       # docs interne
├── docs/partner/                               # docs per partner integratore
├── public/                                     # asset statici
├── resources/views/                            # blade templates
├── routes/web.php, console.php
├── storage/
│   ├── app/                                    # PDF DDT, etichette
│   ├── app/excel_sync/                         # sync_data.xlsx + state
│   ├── framework/cache/
│   ├── framework/sessions/
│   └── logs/laravel.log
├── vendor/                                     # composer dependencies
└── .env

C:pp-staging\                    
```



## Network shares

| Path | Uso | Credenziali env |
|---|---|---|
| `\\<IP ERP>\nettime_timbrature\` | CSV timbrature NetTime | `NETTIME_SHARE_USER`, `NETTIME_SHARE_PASS` |
| `\\<IP gestione presenze>\export\` | Export NetTime PC (TODO config) | `NETTIME_PC_USER`, `NETTIME_PC_PASS`, `NETTIME_EXPORT_CMD` |

Montaggio share: Windows credential manager o `net use` con `/persistent:yes`.

## Environment variables principali (.env)

```
APP_NAME=MES
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://app.cliente.it
LOG_LEVEL=error
LOG_CHANNEL=daily

# DB MES
DB_CONNECTION=mysql
DB_HOST=db.cliente.local
DB_PORT=3306
DB_DATABASE=mossa37
DB_USERNAME=...
DB_PASSWORD=...

# DB Onda
DB_ONDA_HOST=erp.cliente.local
DB_ONDA_PORT=1433
DB_ONDA_DATABASE=...
DB_ONDA_USERNAME=...
DB_ONDA_PASSWORD=...

# Session/Queue/Cache
SESSION_DRIVER=database
SESSION_LIFETIME=480
SESSION_ENCRYPT=true
QUEUE_CONNECTION=database
CACHE_STORE=database

# Prinect
PRINECT_API_URL=...
PRINECT_USERNAME=...
PRINECT_PASSWORD=...
PRINECT_DEVICE_XL106_ID=4001

# Fiery
FIERY_HOST=...
FIERY_USERNAME=...
FIERY_PASSWORD=...

# BRT
BRT_USERID=...
BRT_PASSWORD=...
BRT_VERIFY_SSL=true

# Telegram
TELEGRAM_BOT_TOKEN=...
TELEGRAM_WEBHOOK_SECRET=...

# Claude Vision (OCR bolle)
ANTHROPIC_API_KEY=...

# Excel
EXCEL_SYNC_PATH=C:pp\storage\app\excel_sync\sync_data.xlsx

# NetTime
NETTIME_SHARE_USER=...
NETTIME_SHARE_PASS=...

# Cron config
REPORT_CONTATORI_TO=admin@cliente.it
PIANO_EMAIL_ENABLED=false
PIANO_EMAIL_ORA=06:00
PIANO_EMAIL_TO=direzione@cliente.it
ALERT_RITARDI_ENABLED=false

# Sicurezza
TRUSTED_PROXIES=*
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=
FORCE_HTTPS=true
```

## Deployment process (manuale)

### 1. Connessione

- SSH disponibile (oppure AnyDesk/RustDesk per accesso remoto IT)
- `cd C:pp\` (master) o `cd C:pp-staging\` ()

### 2. Pull + dipendenze

```
git pull origin main              # o 
composer install --no-dev --optimize-autoloader
npm ci && npm run build              # se asset frontend modificati
```

### 3. Migrate + cache

```
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache              # listener auto-discovery
```

### 4. Restart

```
# Restart Apache (se config Apache modificato)
net stop Apache2.4 && net start Apache2.4

# Restart queue worker (per ricaricare codice job)
php artisan queue:restart

# Restart Telegram bot service
nssm restart Mossa37TelegramBot
```

### 5. Verifica

```
curl https://app.cliente.it/health    # → "MES OK"
```

Controllo log:
- `storage/logs/laravel-YYYY-MM-DD.log`
- Apache error log
- Audit log via `/owner/audit-log`

### Rollback

```
git reset --hard <previous_commit>
composer install --no-dev
php artisan migrate:rollback        # solo se serve
php artisan config:cache
```

## Backup

| Cosa | Frequenza | Strumento | Note |
|---|---|---|---|
| MySQL DB | Giornaliero | `mysqldump --single-transaction` | TODO: documentare cronologia |
| File storage | Manuale snapshot | TODO automatizzare |
| Onda DB | Esterno (fornitore Onda) | gestito da fornitore |
| Codice | Git remote (GitHub privato) | Push frequente |

**Restore disaster recovery:**
1. Restore MySQL dump
2. Clone repo, checkout tag/commit specifico
3. `composer install`
4. Restore `.env`
5. `php artisan migrate --force`
6. Restart services

## Monitoring

- **Health endpoint:** `GET /health` → response `"MES OK"` 200
- **Audit log:** tabella `audit_logs`, vista `/owner/audit-log` (90gg default retention)
- **Application log:** `storage/logs/laravel-YYYY-MM-DD.log` (daily channel)
- **Apache log:** `%APACHE_HOME%/logs/`
- **Queue failed:** `php artisan queue:failed`

> Per monitoring esterno (uptime, response time) si consiglia health check da servizio cloud su `/health`.

## Considerazioni per deploy partner integratore

### Multi-tenant: opzioni

**Opzione A — Istanza dedicata per cliente** (più semplice):
- Codebase clonata per ogni tenant
- DB MySQL separato (`mossa37_clienteX`)
- `.env` per tenant
- Subdomain o porta dedicata
- Cron e queue separati

**Opzione B — Multi-tenant column** (richiede refactor):
- Tabelle con `tenant_id` column
- Middleware `TenantResolver` per filtrare query
- `TenantManager` per switch DB connection
- Sessioni isolate per tenant

> Branch  predispone codebase per opzione B (vedi roadmap).

### Port mapping consigliato

| Porta | Servizio |
|---|---|
| 80/443 | Apache MES (HTTPS) |
| 3306 | MySQL (non esposto pubblicamente) |
| 1433 | SQL Server Onda (se Onda interno) |
| - | Outbound: Prinect REST, Fiery REST, BRT SOAP, Telegram API, Anthropic API |

### Requisiti hardware (per ogni tenant)

| Risorsa | Minimo | Raccomandato |
|---|---|---|
| CPU | 4 core | 8 core |
| RAM | 8 GB | 16 GB |
| Disco | 100 GB SSD | 250 GB SSD |
| Banda | 10 Mbps | 50 Mbps |

### Checklist installazione tenant

- [ ] Server Windows o Linux pronto (PHP 8.2+, MySQL 8, Apache 2.4)
- [ ] Repo clonato + composer install
- [ ] DB creato + migrate
- [ ] `.env` configurato con credenziali tenant (Onda, Prinect, Fiery, BRT, ecc.)
- [ ] Cron Task Scheduler configurato (`schedule:run` ogni minuto)
- [ ] Queue worker (`queue_worker.bat` o systemd)
- [ ] Telegram bot service (se richiesto)
- [ ] SSL/HTTPS configurato
- [ ] Backup MySQL automatico
- [ ] Monitoring `/health` esterno
- [ ] Audit log retention configurata
- [ ] Utente admin creato + 2FA attivato
- [ ] Operatori importati con codici + ruoli + reparti
- [ ] Test sync Onda primo run
- [ ] Test sync Prinect/Fiery
- [ ] Smoke test dashboard owner/operatore/spedizione
