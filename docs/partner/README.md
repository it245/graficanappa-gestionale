# Mossa 37 — Documentazione Tecnica

© 2025-2026 Grafica Nappa S.r.l. — Tutti i diritti riservati.
Documento riservato. Distribuzione limitata ai partner autorizzati.

---

## Indice

1. [Executive Summary](docs/01-executive-summary.md) — cosa fa Mossa 37 e perché esiste
2. [Architettura](docs/02-architecture.md) — Laravel 12 + DDD modulare
3. [Stack Tecnico](docs/03-stack.md) — PHP 8.5, MySQL, Apache, Windows Server
4. [Moduli Funzionali](docs/04-modules.md) — Onda, Operatori, Spedizione, Prinect, Scheduling
5. [Integrazioni](docs/05-integrations.md) — Onda SQL Server, Prinect REST, BRT, NetTime, Solar-Log, Telegram
6. [Database](docs/06-database.md) — schema 115 tabelle, ER principale
7. [Flussi](docs/07-flows.md) — sequence diagrams: sync Onda, ciclo fase, DDT
8. [Deployment](docs/08-deployment.md) — Windows Server, cron Task Scheduler, queue worker
9. [Sicurezza e GDPR](docs/09-security.md) — 2FA, ruoli, audit log, art. 4 Statuto Lavoratori

---

## Panoramica rapida

| Voce | Valore |
|---|---|
| Linguaggio | PHP 8.5 |
| Framework | Laravel 12 |
| Database operativo | MySQL 8 |
| Database ERP sorgente | SQL Server (Onda) |
| Architettura | DDD modulare (19 moduli) |
| Moduli `app/Modules/` | Onda, Operatori, Spedizione, Prinect, Magazzino, Scheduling, Fasi, Carta, Fustelle, Macchine, Notifiche, Presenze, Reparti, Reportistica, Stampa, Commessa, Documenti, Audit |
| Migrations DB | 115 |
| Rotte web | 427 righe `routes/web.php` |
| Tipo deploy | On-premise Windows Server + Apache |
| Cron | Windows Task Scheduler (`schedule:run` ogni minuto) |
| Queue | Database driver + worker batch ogni 2 min |
| Sync ERP | Onda SQL Server ogni ora |
| Sync Prinect (stampa) | REST API ogni minuto |
| Sync Fiery (digitale) | Accounting API ogni minuto |
| Excel bidirezionale | Ogni 2 minuti |

---

## Cosa fa Mossa 37

Sistema di esecuzione manifatturiera per **tipografia industriale (stampa offset + digitale + finitura + legatoria + spedizione)**.

Funzioni principali:

- **Tracciamento commesse**: dal recepimento ordine cliente (via ERP Onda) alla spedizione DDT
- **Stato fasi real-time**: ogni operatore registra avvio/pausa/termine fase su dashboard touch
- **Sincronizzazione macchine**: Prinect (stampa offset XL106) + Fiery (digitale) inviano fogli prodotti, scarti, tempi automatici
- **Schedulazione produzione**: scheduler con propagazione fasi e ottimizzazione setup batch
- **Spedizione**: gestione DDT BRT + corriere proprio, etichette Data Matrix
- **Reportistica**: ore lavorate vs preventivate, KPI per reparto, marginalità commessa
- **Magazzino carta**: giacenze, movimentazione, allerte sotto-scorta
- **Esterno**: gestione lavorazioni esterne (fustellatura, plastificazione, finitura)
- **Prestampa**: gestione cliché, fustelle, note prestampa, operatore prestampa

---

## Target utenti

| Ruolo | Dashboard | Device |
|---|---|---|
| Direzione | `/owner` | PC desktop |
| Operatore reparto | `/operatore` | Tablet touch + smartphone |
| Spedizione | `/spedizione` | PC + lettore Data Matrix |
| Prestampa | `/operatore/prestampa` | PC |
| Admin IT | `/admin` | PC |
| Fiery operator | `/fiery` | PC |
