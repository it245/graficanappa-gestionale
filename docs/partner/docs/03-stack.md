# 03. Stack Tecnico

## Sintesi

| Componente | Versione | Ruolo |
|---|---|---|
| PHP | 8.5 (min 8.2 da composer) | Runtime backend |
| Laravel | 12 | Framework |
| MySQL | 8 | DB operativo MES |
| SQL Server | (Onda ERP) | Sorgente dati ERP |
| Apache | 2.4 | Web server (FastCGI PHP) |
| Vite | 7 | Bundler frontend |
| Bootstrap | 5.2+ | UI framework |
| Tailwind | 4 | Utility CSS |

## Backend

### Linguaggio + Framework

- **PHP 8.5** in produzione (min 8.2 dichiarato in `composer.json`)
- **Laravel 12** — framework MVC
- Strict types, readonly properties, enum backed (PHP 8.1+ features)

### Dipendenze Composer (`require`)

| Pacchetto | Versione | Scopo |
|---|---|---|
| `laravel/framework` | ^12.0 | Core framework |
| `laravel/reverb` | ^1.8 | WebSocket server (real-time, opzionale) |
| `laravel/tinker` | ^2.10 | REPL CLI |
| `laravel/ui` | ^4.6 | Scaffolding UI auth |
| `barryvdh/laravel-dompdf` | ^3.1 | Generazione PDF (DDT, etichette, schede produzione) |
| `maatwebsite/excel` | ^3.1 | Import/export Excel (PhpSpreadsheet) |
| `simplesoftwareio/simple-qrcode` | ^4.2 | QR e DataMatrix etichette |
| `spatie/laravel-permission` | ^7.4 | RBAC (ruoli/permessi) |
| `thiagoalessio/tesseract_ocr` | ^2.13 | OCR bolle magazzino (foto Telegram) |
| `pusher/pusher-php-server` | ^7.2 | Push notifications (alternativa a Reverb) |

### Dipendenze on-demand (non in composer.json corrente, da verificare branch attivo)

| Pacchetto | Scopo |
|---|---|
| `pragmarx/google2fa` | TOTP 2FA admin |
| `tecnickcom/tcpdf` | PDF avanzati (alternativa) |
| `irazasyed/telegram-bot-sdk` | SDK bot Telegram |
| `minishlink/web-push` | Web Push notifications |
| `phpoffice/phpspreadsheet` | Manipolazione Excel (transitive da maatwebsite/excel) |

## Frontend

### Build tooling

- **Vite 7** — bundler (config `vite.config.js`)
- **laravel-vite-plugin** ^2.0
- **concurrently** ^9 — orchestrazione dev processes
- **sass** ^1.56 — preprocessor

### Libraries UI

| Libreria | Versione | Uso |
|---|---|---|
| Blade templates | (Laravel) | Server-side templating |
| Bootstrap | ^5.2 | UI framework |
| @popperjs/core | ^2.11 | Tooltip/popover positioning |
| Tailwind CSS | ^4.0 | Utility-first CSS |
| @tailwindcss/vite | ^4.0 | Integrazione Vite |
| axios | ^1.11 | HTTP client AJAX |
| Choices.js | (CDN) | Multi-select filtering |
| Handsontable | v14 (CDN) | Excel-like bulk edit (modifica multipla owner) |
| Html5Qrcode | (CDN lazy) | QR scanner etichette |
| Chart.js | (CDN) | KPI visualizations (report ore, dashboard owner) |

### PWA

- Manifest `public/manifest.json`
- Service worker per cache offline
- Push notifications via Web Push API (VAPID keys)
- Installabile su tablet operatori (home screen icon)

### WebSocket (opzionale)

- **Laravel Reverb** + **Laravel Echo** per chat real-time
- In produzione attualmente NON attivo (polling 15s come fallback)

## Storage

| Tipo | Path | Uso |
|---|---|---|
| Filesystem locale | `storage/app/` | PDF DDT, etichette, log applicativi |
| Filesystem locale | `storage/app/excel_sync/` | File Excel bidirezionale (`sync_data.xlsx`) |
| Filesystem locale | `public/` | Asset statici, immagini, manifest |
| Share di rete | `\\.253\nettime_timbrature\` | CSV timbrature NetTime |
| Share di rete | `\\.34\` | Export NetTime PC (TODO config) |

## Deploy

| Componente | Configurazione |
|---|---|
| OS server | Windows Server (Server cliente) |
| Web server | Apache 2.4 con PHP FastCGI |
| HTTPS | Forced via env `force_https` (default off, on in prod) |
| Web app | Apache su porta 80, single deployment |
| DB | MySQL 8 locale, connessione TCP 3306 |
| Cron | Windows Task Scheduler → `php artisan schedule:run` ogni minuto |
| Queue | Driver `database`, worker `queue_worker.bat` invocato ogni 2 min |
| Servizi Windows | `Mossa37TelegramBot` (nssm) per long-polling bot |

> Dettaglio completo in `08-deployment.md`.

## Requisiti minimi server

### Produzione (carico : ~30 utenti concorrenti, 6000 ordini storici)

| Risorsa | Minimo | Raccomandato |
|---|---|---|
| CPU | 4 core | 8 core |
| RAM | 8 GB | 16 GB |
| Disco | 100 GB SSD | 250 GB SSD |
| OS | Windows Server 2019+ | Windows Server 2022 |
| PHP | 8.2 | 8.5 |
| MySQL | 8.0 | 8.0 LTS |
| Apache | 2.4 | 2.4 |

### Sviluppo locale

| Risorsa | Minimo |
|---|---|
| CPU | 4 core |
| RAM | 8 GB |
| Disco | 30 GB |
| OS | Windows 10/11, macOS, Linux |
| PHP | 8.2+ |
| MySQL | 8.0 |
| Node | 18+ (per vite) |
| Composer | 2.5+ |

## Configurazione SSL/TLS

- **HTTPS**: certificato self-signed o Let's Encrypt
- **HSTS**: max-age 1 anno (attivato solo dopo verifica HTTPS stabile)
- **CSP**: Report-Only attualmente (legacy unsafe-inline/unsafe-eval per onclick/Echo)

## Lingua / Localizzazione

- **UI**: italiano
- **Codice**: italiano (nomi metodi, classi, commenti)
- **Database**: italiano (nomi colonne, tabelle)
- **Date**: formato italiano (`d/m/Y`, `d/m/Y H:i:s`)
- **Decimali**: separatore `,` (visualizzazione), `.` (storage)
